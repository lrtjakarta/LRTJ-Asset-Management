<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;

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

        $username = trim((string) $data['username']);
        $password = (string) $data['password'];
        $remember = $request->boolean('remember');

        // --- Rate limiting ---------------------------------------------------
        $key = 'ldap-login:' . Str::lower($username) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'username' => "Too many login attempts. Try again in {$seconds} seconds.",
            ])->status(429);
        }

        RateLimiter::hit($key, 60);

        // --- STATIC ADMIN SHORT-CIRCUIT -------------------------------------
        $staticUser = strtolower((string) config('auth.static_admin.username'));
        $staticPass = (string) config('auth.static_admin.password');

        if (
            $staticUser !== '' &&
            $staticPass !== '' &&
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

            return redirect()->intended(route('dashboard.monthly'))
                ->with('success', 'Welcome, admin!');
        }

        // --- 1) LOCAL DB LOGIN ----------------------------------------------
        // Tetap support login lokal, tapi jangan sampai error DB menghentikan LDAP.
        try {
            if (
                Auth::attempt(
                    ['username' => $username, 'password' => $password],
                    $remember
                )
            ) {
                $request->session()->regenerate();
                RateLimiter::clear($key);

                return redirect()->intended(route('dashboard.monthly'));
            }
        } catch (\Throwable $e) {
            \Log::warning('Local DB login skipped, fallback to LDAP', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
        }

        // --- 2) LDAP CONFIG - mengikuti ENV kamu -----------------------------
        $connectionName = env('LDAP_CONNECTION', 'default');
        $host           = env('LDAP_HOST');
        $port           = (int) env('LDAP_PORT', 389);
        $baseDn         = trim((string) env('LDAP_BASE_DN'), '"');
        $bindDn         = env('LDAP_USERNAME');   // mengikuti env kamu
        $bindPassword   = env('LDAP_PASSWORD');   // mengikuti env kamu
        $domain         = env('LDAP_DOMAIN');
        $timeout        = (int) env('LDAP_TIMEOUT', 5);
        $useSsl         = filter_var(env('LDAP_SSL', false), FILTER_VALIDATE_BOOLEAN);
        $useTls         = filter_var(env('LDAP_TLS', false), FILTER_VALIDATE_BOOLEAN);

        if (empty($host) || empty($baseDn)) {
            \Log::error('LDAP configuration incomplete', [
                'connection' => $connectionName,
                'host_exists' => !empty($host),
                'base_dn_exists' => !empty($baseDn),
            ]);

            throw ValidationException::withMessages([
                'username' => 'LDAP configuration is incomplete. Please contact administrator.',
            ]);
        }

        \Log::info('LDAP login attempt started', [
            'username' => $username,
            'connection' => $connectionName,
            'host' => $host,
            'port' => $port,
            'baseDn' => $baseDn,
            'domain' => $domain,
            'bindDnExists' => !empty($bindDn),
            'bindPasswordExists' => !empty($bindPassword),
            'ssl' => $useSsl,
            'tls' => $useTls,
        ]);

        // --- 3) LDAP AUTHENTICATION ------------------------------------------
        $authenticated = false;
        $attrs = [];

        /*
        * Karena env kamu Active Directory:
        * LDAP_BASE_DN="DC=office,DC=xx,DC=co,DC=id"
        *
        * Maka kandidat login user:
        * - username@LDAP_DOMAIN
        * - username langsung kalau input sudah email/UPN
        * - uid=username,BASE_DN sebagai fallback lama
        */
        $candidateDns = [];

        if (str_contains($username, '@')) {
            $candidateDns[] = $username;
        }

        if (!empty($domain) && !str_contains($username, '@')) {
            $candidateDns[] = $username . '@' . $domain;
        }

        $netbios = env('LDAP_NETBIOS');
        if (!empty($netbios) && !str_contains($username, '\\')) {
            $candidateDns[] = $netbios . '\\' . $username;
        }

        // fallback lama untuk OpenLDAP
        $candidateDns[] = "uid={$username},{$baseDn}";

        $candidateDns = array_values(array_unique(array_filter($candidateDns)));

        foreach ($candidateDns as $candidateDn) {
            try {
                if ($this->ldapBind($host, $port, $candidateDn, $password, $timeout, $useSsl, $useTls)) {
                    $authenticated = true;

                    \Log::info('LDAP bind success', [
                        'username' => $username,
                        'bindAs' => $this->maskLdapDn($candidateDn),
                    ]);

                    break;
                }

                \Log::warning('LDAP bind failed', [
                    'username' => $username,
                    'bindAs' => $this->maskLdapDn($candidateDn),
                ]);
            } catch (\Throwable $e) {
                \Log::warning('LDAP bind exception', [
                    'username' => $username,
                    'bindAs' => $this->maskLdapDn($candidateDn),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'username' => 'Invalid credentials.',
            ]);
        }

        // --- 4) FETCH LDAP ATTRIBUTES pakai LDAP_USERNAME & LDAP_PASSWORD -----
        try {
            $attrs = $this->ldapFetchAttributes(
                $host,
                $port,
                $bindDn,
                $bindPassword,
                $baseDn,
                $username,
                $timeout,
                $useSsl,
                $useTls
            ) ?? [];
        } catch (\Throwable $e) {
            \Log::warning('LDAP attributes fetch failed, continue with basic username', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            $attrs = [];
        }

        $cn = $attrs['cn'][0]
            ?? $attrs['displayname'][0]
            ?? $attrs['displayName'][0]
            ?? $attrs['name'][0]
            ?? $username;

        $email = $attrs['mail'][0]
            ?? $attrs['userprincipalname'][0]
            ?? $attrs['userPrincipalName'][0]
            ?? (str_contains($username, '@') ? $username : null);

        $ous = [];

        foreach ($candidateDns as $candidateDn) {
            $ous = array_unique(array_merge($ous, $this->extractOusFromDn($candidateDn)));
        }

        $ous = array_unique(array_merge($ous, $this->extractOusFromMemberOf($attrs)));

        if (!empty($attrs['ou']) && isset($attrs['ou']['count'])) {
            for ($i = 0; $i < $attrs['ou']['count']; $i++) {
                $ous[] = $attrs['ou'][$i];
            }
        }

        $ou = array_values(array_filter(array_unique($ous)))[0] ?? null;

        // --- 5) SYNC LOCAL USER + LOGIN --------------------------------------
        $this->syncAndLoginUser(
            $request,
            username: $username,
            name: $cn,
            email: $email,
            ou: $ou,
            isStaticAdmin: false,
        );

        RateLimiter::clear($key);

        \Log::info('LDAP login success and local user synced', [
            'username' => $username,
            'email' => $email,
            'ou' => $ou,
        ]);

        return redirect()->intended(route('dashboard.monthly'));
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
        $user = User::findForDirectoryIdentity($username, $email);
        $isNew = $user === null;

        if (! $user) {
            $user = User::create([
                'username' => $username,
                'name'     => $name,
                'email'    => $email,
                'ou'       => $ou,
                'password' => $isStaticAdmin
                    ? Hash::make((string) config('auth.static_admin.password'))
                    : Hash::make(Str::random(64)),
            ]);
        }

        // Keep some basic info in sync with LDAP (optional)
        if (
            strcasecmp((string) $user->username, $username) !== 0 &&
            ! User::query()
                ->whereKeyNot($user->getKey())
                ->whereRaw('LOWER(username) = ?', [Str::lower($username)])
                ->exists()
        ) {
            $user->username = $username;
        }

        $user->name = $name;

        if (
            $email &&
            ! User::query()
                ->whereKeyNot($user->getKey())
                ->whereRaw('LOWER(email) = ?', [Str::lower($email)])
                ->exists()
        ) {
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

    private function maskLdapDn(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (str_contains($value, '@')) {
            [$local, $domain] = array_pad(explode('@', $value, 2), 2, '');

            if ($local === '') {
                return '***@' . $domain;
            }

            return substr($local, 0, 2) . '***@' . $domain;
        }

        if (str_contains($value, '\\')) {
            [$prefix, $account] = array_pad(explode('\\', $value, 2), 2, '');

            if ($account === '') {
                return $prefix . '\\***';
            }

            return $prefix . '\\' . substr($account, 0, 2) . '***';
        }

        return preg_replace('/^(.{0,2}).*/', '$1***', $value) ?? '***';
    }
}
