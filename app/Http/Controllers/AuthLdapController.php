<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;

class AuthLdapController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $username = $data['username'];
        $password = $data['password'];

        $key = 'ldap-login:' . Str::lower($request->input('username')) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'username' => "Too many login attempts. Try again in {$seconds} seconds.",
            ])->status(429);
        }

        RateLimiter::hit($key, 60);


        // --- STATIC ADMIN SHORT-CIRCUIT ---
        $staticUser = strtolower((string) config('auth.static_admin.username'));
        $staticPass = (string) config('auth.static_admin.password');

        if (
            $staticUser !== '' && $staticPass !== ''
            && hash_equals($staticUser, $username)
            && hash_equals($staticPass, $password)
        ) {

            $request->session()->put('ldap_user', [
                'uid'   => $username,
                'name'  => 'Administrator',
                'dn'    => 'cn=admin,dc=local',
                'ou'    => 'local',
                'auth_via' => 'static',
            ]);
            $request->session()->regenerate();

            RateLimiter::clear($key);
            return redirect()->intended(route('dashboard'))
                ->with('success', 'Welcome, admin!');
        }

        $host    = env('LDAP_HOST', 'ldap.forumsys.com');
        $port    = (int) env('LDAP_PORT', 389);
        $baseDn  = env('LDAP_BASE_DN', 'dc=example,dc=com');
        $roDn    = env('LDAP_BIND_DN');           // cn=read-only-admin,dc=example,dc=com
        $roPass  = env('LDAP_BIND_PASSWORD');
        $timeout = (int) env('LDAP_TIMEOUT', 5);

        // 1) Try direct bind with uid DN
        $userDn = "uid={$username},{$baseDn}";
        if ($this->ldapBind($host, $port, $userDn, $password, $timeout)) {
            $attrs = $this->ldapFetchAttributes($host, $port, $roDn, $roPass, $baseDn, $username, $timeout);
            $cn = $attrs['cn'][0] ?? $username;
            $ous = [];

            $ous = array_unique(array_merge($ous, $this->extractOusFromDn($userDn /* or $foundDn */)));
            // from memberOf (if server returns it)
            $ous = array_unique(array_merge($ous, $this->extractOusFromMemberOf($attrs)));
            // from LDAP attributes (if present)
            if (!empty($attrs['ou'])) {
                for ($i = 0; $i < $attrs['ou']['count']; $i++) {
                    $ous[] = $attrs['ou'][$i];
                }
            }

            $request->session()->put('ldap_user', [
                'dn'   => $userDn,
                'uid'  => $username,
                'name' => $cn,
                'ou'   => $ous[0] ?? null,
                'ous'  => $ous,
                'auth_via' => 'ldap',
            ]);
            $request->session()->regenerate();
            RateLimiter::clear($key);

            return redirect()->intended(route('dashboard'));
        }

        // 2) Fallback: find DN via RO bind, then bind with user password
        $foundDn = $this->ldapFindUserDn($host, $port, $roDn, $roPass, $baseDn, $username, $timeout);
        if ($foundDn && $this->ldapBind($host, $port, $foundDn, $password, $timeout)) {
            $attrs = $this->ldapFetchAttributes($host, $port, $roDn, $roPass, $baseDn, $username, $timeout);
            $cn = $attrs['cn'][0] ?? $username;
            $ous = [];

            $ous = array_unique(array_merge($ous, $this->extractOusFromDn($userDn /* or $foundDn */)));
            // from memberOf (if server returns it)
            $ous = array_unique(array_merge($ous, $this->extractOusFromMemberOf($attrs)));
            // from LDAP attributes (if present)
            if (!empty($attrs['ou'])) {
                for ($i = 0; $i < $attrs['ou']['count']; $i++) {
                    $ous[] = $attrs['ou'][$i];
                }
            }

            $request->session()->put('ldap_user', [
                'dn'   => $foundDn,
                'uid'  => $username,
                'name' => $cn,
                'ou'   => $ous[0] ?? null,
                'ous'  => $ous,
                'auth_via' => 'ldap',
            ]);
            $request->session()->regenerate();
            RateLimiter::clear($key);
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['username' => 'Invalid LDAP credentials.'])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('ldap_user');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('ldap.login');
    }

    /* ---------- helpers ---------- */

    private function ldapConnect(string $host, int $port, int $timeout)
    {
        $conn = @ldap_connect($host, $port);
        if (! $conn) {
            return null;
        }
        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, $timeout);
        return $conn;
    }

    private function ldapBind(string $host, int $port, string $dn, string $password, int $timeout): bool
    {
        $conn = $this->ldapConnect($host, $port, $timeout);
        if (! $conn) return false;
        $ok = @ldap_bind($conn, $dn, $password);
        ldap_unbind($conn);
        return (bool) $ok;
    }

    private function ldapFindUserDn(string $host, int $port, ?string $bindDn, ?string $bindPass, string $baseDn, string $username, int $timeout): ?string
    {
        $conn = $this->ldapConnect($host, $port, $timeout);
        if (! $conn) return null;

        // Bind as read-only admin to search
        if ($bindDn && $bindPass) {
            if (! @ldap_bind($conn, $bindDn, $bindPass)) {
                ldap_unbind($conn);
                return null;
            }
        } else {
            @ldap_bind($conn);
        }

        $filter = sprintf('(uid=%s)', ldap_escape($username, '', LDAP_ESCAPE_FILTER));
        $attrs  = ['dn'];
        $sr     = @ldap_search($conn, $baseDn, $filter, $attrs, 0, 1);
        if (! $sr) {
            ldap_unbind($conn);
            return null;
        }

        $entries = ldap_get_entries($conn, $sr);
        ldap_unbind($conn);

        if ($entries && $entries['count'] > 0) {
            return $entries[0]['dn'] ?? null;
        }
        return null;
    }

    private function ldapFetchAttributes(string $host, int $port, ?string $bindDn, ?string $bindPass, string $baseDn, string $username, int $timeout): array
    {
        $conn = $this->ldapConnect($host, $port, $timeout);
        if (! $conn) return [];

        if ($bindDn && $bindPass) {
            if (! @ldap_bind($conn, $bindDn, $bindPass)) {
                ldap_unbind($conn);
                return [];
            }
        } else {
            @ldap_bind($conn);
        }

        $filter = sprintf('(uid=%s)', ldap_escape($username, '', LDAP_ESCAPE_FILTER));
        $attrs  = ['cn', 'uid', 'mail', 'memberOf', 'sn', 'givenName', 'ou'];
        $sr     = @ldap_search($conn, $baseDn, $filter, $attrs, 0, 1);
        if (! $sr) {
            ldap_unbind($conn);
            return [];
        }

        $entries = ldap_get_entries($conn, $sr);
        ldap_unbind($conn);

        if ($entries && $entries['count'] > 0) {
            return $entries[0];
        }
        return [];
    }
    private function extractOusFromDn(string $dn): array
    {
        // ldap_explode_dn is from ldap php extention
        if (function_exists('ldap_explode_dn')) {
            $parts = @ldap_explode_dn($dn, 0);
            if (is_array($parts)) {
                $out = [];
                for ($i = 0; $i < ($parts['count'] ?? 0); $i++) {
                    $p = $parts[$i];
                    if (stripos($p, 'ou=') === 0) {
                        $out[] = substr($p, 3);
                    }
                }
                return $out;
            }
        }

        // Fallback: regex parse
        preg_match_all('/ou=([^,]+)/i', $dn, $m);
        return array_map('strval', $m[1] ?? []);
    }

    private function extractOusFromMemberOf(array $attrs): array
    {
        $res = [];
        if (!empty($attrs['memberof'])) {
            for ($i = 0; $i < $attrs['memberof']['count']; $i++) {
                $dn = $attrs['memberof'][$i];
                // Try OU first
                if (preg_match('/ou=([^,]+)/i', $dn, $m)) {
                    $res[] = $m[1];
                    // Some directories use CN groups; keep CN as a “group” name fallback
                } elseif (preg_match('/cn=([^,]+)/i', $dn, $m)) {
                    $res[] = $m[1];
                }
            }
        }
        return $res;
    }
}
