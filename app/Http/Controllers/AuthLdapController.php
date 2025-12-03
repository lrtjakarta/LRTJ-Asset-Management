<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

        // --- Rate limiting ---------------------------------------------------
        $key = 'ldap-login:' . Str::lower($request->input('username')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'username' => "Too many login attempts. Try again in {$seconds} seconds.",
            ])->status(429);
        }

        RateLimiter::hit($key, 60); // 5 attempts per 60 seconds

        // --- STATIC ADMIN SHORT-CIRCUIT -------------------------------------
        $staticUser = strtolower((string) config('auth.static_admin.username'));
        $staticPass = (string) config('auth.static_admin.password');

        if (
            $staticUser !== '' && $staticPass !== '' &&
            hash_equals($staticUser, strtolower($username)) &&
            hash_equals($staticPass, $password)
        ) {
            $this->syncAndLoginUser(
                $request,
                username: $username,
                name: 'Administrator',
                email: 'admin@example.com',
                ou: 'local',
                isStaticAdmin: true,
            );

            RateLimiter::clear($key);

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Welcome, admin!');
        }

        // --- 1) LOCAL DB LOGIN (users table, username + hashed password) -----
        if (
            Auth::attempt(
                ['username' => $username, 'password' => $password],
                $request->boolean('remember')
            )
        ) {
            $request->session()->regenerate();
            RateLimiter::clear($key);

            return redirect()->intended(route('dashboard'));
        }

        // --- 2) LDAP CONFIG --------------------------------------------------
        $host    = env('LDAP_HOST', 'ldap.forumsys.com');
        $port    = (int) env('LDAP_PORT', 389);
        $baseDn  = env('LDAP_BASE_DN', 'dc=example,dc=com');
        $roDn    = env('LDAP_BIND_DN');
        $roPass  = env('LDAP_BIND_PASSWORD');
        $timeout = (int) env('LDAP_TIMEOUT', 5);

        $userDn = "uid={$username},{$baseDn}";

        // --- 3) Try direct bind ---------------------------------------------
        if ($this->ldapBind($host, $port, $userDn, $password, $timeout)) {
            $attrs = $this->ldapFetchAttributes($host, $port, $roDn, $roPass, $baseDn, $username, $timeout);

            $cn    = $attrs['cn'][0]   ?? $username;
            $email = $attrs['mail'][0] ?? null;

            $ous = [];
            $ous = array_unique(array_merge($ous, $this->extractOusFromDn($userDn)));
            $ous = array_unique(array_merge($ous, $this->extractOusFromMemberOf($attrs)));
            if (!empty($attrs['ou'])) {
                for ($i = 0; $i < $attrs['ou']['count']; $i++) {
                    $ous[] = $attrs['ou'][$i];
                }
            }
            $ou = $ous[0] ?? null;

            // Only auth via LDAP; roles & departments are internal
            $this->syncAndLoginUser(
                $request,
                username: $username,
                name: $cn,
                email: $email,
                ou: $ou,
                isStaticAdmin: false,
            );

            RateLimiter::clear($key);

            return redirect()->intended(route('dashboard'));
        }

        // --- 4) Fallback: search DN then bind -------------------------------
        $foundDn = $this->ldapFindUserDn($host, $port, $roDn, $roPass, $baseDn, $username, $timeout);

        if ($foundDn && $this->ldapBind($host, $port, $foundDn, $password, $timeout)) {
            $attrs = $this->ldapFetchAttributes($host, $port, $roDn, $roPass, $baseDn, $username, $timeout);

            $cn    = $attrs['cn'][0]   ?? $username;
            $email = $attrs['mail'][0] ?? null;

            $ous = [];
            $ous = array_unique(array_merge($ous, $this->extractOusFromDn($foundDn)));
            $ous = array_unique(array_merge($ous, $this->extractOusFromMemberOf($attrs)));
            if (!empty($attrs['ou'])) {
                for ($i = 0; $i < $attrs['ou']['count']; $i++) {
                    $ous[] = $attrs['ou'][$i];
                }
            }
            $ou = $ous[0] ?? null;

            $this->syncAndLoginUser(
                $request,
                username: $username,
                name: $cn,
                email: $email,
                ou: $ou,
                isStaticAdmin: false,
            );

            RateLimiter::clear($key);

            return redirect()->route('dashboard');
        }

        // --- 5) Failed -------------------------------------------------------
        return back()
            ->withErrors(['username' => 'Invalid credentials.'])
            ->onlyInput('username');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('ldap.login');
    }

    /**
     * Sync LDAP user into local users table & log them in.
     *
     * - LDAP is only for auth.
     * - Roles and kode_department are managed internally.
     */
    private function syncAndLoginUser(
        Request $request,
        string $username,
        string $name,
        ?string $email,
        ?string $ou,
        bool $isStaticAdmin = false,
    ): void {
        // Create user if not exists; do NOT set kode_department here.
        $user = User::firstOrCreate(
            ['username' => $username],
            [
                'name'  => $name,
                'email' => $email,
                'ou'    => $ou,
            ]
        );

        $isNew = $user->wasRecentlyCreated;

        // Keep some basic info in sync with LDAP (optional)
        $user->name = $name;
        if ($email) {
            $user->email = $email;
        }
        if ($ou) {
            $user->ou = $ou;
        }
        $user->save();

        // ROLES:
        //  - Static admin: force SYSADMIN
        //  - Others: give default AUDITOR only on first login if no role yet
        //  - After that, all role changes are via internal UI.

        if ($isStaticAdmin) {
            $user->roles()->syncWithoutDetaching(['SYSADMIN']);

            if (empty($user->role_kode)) {
                $user->role_kode = 'SYSADMIN';
                $user->save();
            }
        } else {
            if ($isNew && empty($user->role_kode)) {
                $user->roles()->syncWithoutDetaching(['AUDITOR']);
                $user->role_kode = 'AUDITOR';
                $user->save();
            }
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();
    }

    /* ---------------------------------------------------------------------- */
    /* ----------------------------- LDAP helpers --------------------------- */
    /* ---------------------------------------------------------------------- */

    private function ldapConnect(string $host, int $port, int $timeout)
    {
        $conn = @ldap_connect($host, $port);
        if (!$conn) {
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
        if (!$conn) {
            return false;
        }

        $ok = @ldap_bind($conn, $dn, $password);
        ldap_unbind($conn);

        return (bool) $ok;
    }

    private function ldapFindUserDn(
        string $host,
        int $port,
        ?string $bindDn,
        ?string $bindPass,
        string $baseDn,
        string $username,
        int $timeout
    ): ?string {
        $conn = $this->ldapConnect($host, $port, $timeout);
        if (!$conn) {
            return null;
        }

        if ($bindDn && $bindPass) {
            if (!@ldap_bind($conn, $bindDn, $bindPass)) {
                ldap_unbind($conn);
                return null;
            }
        } else {
            @ldap_bind($conn);
        }

        $filter = sprintf('(uid=%s)', ldap_escape($username, '', LDAP_ESCAPE_FILTER));
        $attrs  = ['dn'];

        $sr = @ldap_search($conn, $baseDn, $filter, $attrs, 0, 1);
        if (!$sr) {
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

    private function ldapFetchAttributes(
        string $host,
        int $port,
        ?string $bindDn,
        ?string $bindPass,
        string $baseDn,
        string $username,
        int $timeout
    ): array {
        $conn = $this->ldapConnect($host, $port, $timeout);
        if (!$conn) {
            return [];
        }

        if ($bindDn && $bindPass) {
            if (!@ldap_bind($conn, $bindDn, $bindPass)) {
                ldap_unbind($conn);
                return [];
            }
        } else {
            @ldap_bind($conn);
        }

        $filter = sprintf('(uid=%s)', ldap_escape($username, '', LDAP_ESCAPE_FILTER));

        // 'memberOf' will come back as 'memberof' key (lowercase) in PHP
        $attrs  = ['cn', 'uid', 'mail', 'memberOf', 'sn', 'givenName', 'ou', 'departmentNumber'];

        $sr = @ldap_search($conn, $baseDn, $filter, $attrs, 0, 1);
        if (!$sr) {
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
        if (function_exists('ldap_explode_dn')) {
            $parts = @ldap_explode_dn($dn, 0);
            if (is_array($parts)) {
                $out = [];
                $count = $parts['count'] ?? 0;

                for ($i = 0; $i < $count; $i++) {
                    $p = $parts[$i];
                    if (stripos($p, 'ou=') === 0) {
                        $out[] = substr($p, 3);
                    }
                }

                return $out;
            }
        }

        preg_match_all('/ou=([^,]+)/i', $dn, $m);

        return array_map('strval', $m[1] ?? []);
    }

    private function extractOusFromMemberOf(array $attrs): array
    {
        $res = [];

        if (!empty($attrs['memberof'])) {
            for ($i = 0; $i < $attrs['memberof']['count']; $i++) {
                $dn = $attrs['memberof'][$i];

                if (preg_match('/ou=([^,]+)/i', $dn, $m)) {
                    $res[] = $m[1];
                } elseif (preg_match('/cn=([^,]+)/i', $dn, $m)) {
                    $res[] = $m[1];
                }
            }
        }

        return $res;
    }
}
