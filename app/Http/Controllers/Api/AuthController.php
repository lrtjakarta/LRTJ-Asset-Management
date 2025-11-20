<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MasterRoleMenu;
use Illuminate\Http\Request;
use App\Services\LdapAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\TransientToken;

class AuthController extends Controller
{
    public function __construct(private LdapAuth $ldap) {}

    public function login(Request $request)
    {
        $request->validate([
            'username'    => ['required', 'string'],
            'password'    => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $key = 'api-login:' . Str::lower($request->input('username')) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Too many attempts. Try again in {$seconds} seconds."
            ], 429);
        }
        RateLimiter::hit($key, 60);

        $username = Str::lower(trim($request->input('username')));
        $password = (string) $request->input('password');
        $device   = $request->input('device_name', 'api');

        // --- STATIC ADMIN SHORT-CIRCUIT (same idea as web) ---
        $staticUser = Str::lower((string) config('auth.static_admin.username', ''));
        $staticPass = (string) config('auth.static_admin.password', '');
        if (
            $staticUser !== '' && $staticPass !== '' &&
            hash_equals($staticUser, $username) && hash_equals($staticPass, $password)
        ) {
            $user = User::firstOrCreate(
                ['username' => $staticUser],
                [
                    'name'     => 'Administrator',
                    'email'    => 'admin@example.com',
                    'password' => Hash::make(Str::random(32)),
                ]
            );

            // Ensure SYSADMIN role attached (like web AuthLdapController)
            if (! $user->roles()->where('kode', 'SYSADMIN')->exists()) {
                $user->roles()->syncWithoutDetaching(['SYSADMIN']);
            }

            $token = $user->createToken($device)->plainTextToken;
            RateLimiter::clear($key);

            return response()->json([
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user'         => $this->buildUserPayload($user, [
                    'auth' => 'static',
                    // no DN for static user
                ]),
            ], 201);
        }

        // --- LDAP LOGIN (mirrors web logic as much as possible) ---
        $host    = config('ldap.host');
        $port    = (int) config('ldap.port', 389);
        $baseDn  = (string) config('ldap.base_dn');
        $roDn    = config('ldap.bind_dn');
        $roPass  = config('ldap.bind_pass');
        $timeout = (int) config('ldap.timeout', 5);

        $userDn = "uid={$username},{$baseDn}";
        $ok     = $host && $baseDn && $this->ldap->bindDn($host, $port, $userDn, $password, $timeout);

        if (! $ok && $host && $baseDn) {
            $foundDn = $this->ldap->findUserDn($host, $port, $roDn, $roPass, $baseDn, $username, $timeout);
            if ($foundDn) {
                $userDn = $foundDn;
                $ok = $this->ldap->bindDn($host, $port, $foundDn, $password, $timeout);
            }
        }

        if ($ok) {
            $attrs = $this->ldap->fetchAttributes($host, $port, $roDn, $roPass, $baseDn, $username, $timeout);
            $cn    = $attrs['cn'][0]   ?? $username;
            $mail  = $attrs['mail'][0] ?? null;

            // kode_department from LDAP like web AuthLdapController
            $kodeDepartment = $attrs['departmentNumber'][0] ?? null;

            $user = User::updateOrCreate(
                ['username' => $username],
                [
                    'name'            => $cn,
                    'email'           => $mail,
                    'password'        => Hash::make(Str::random(32)),
                    'kode_department' => $kodeDepartment,
                ]
            );

            $token = $user->createToken($device)->plainTextToken;
            RateLimiter::clear($key);

            return response()->json([
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user'         => $this->buildUserPayload($user, [
                    'auth' => 'ldap',
                    'dn'   => $userDn,
                ]),
            ], 201);
        }

        return response()->json(['message' => 'Invalid credentials.'], 422);
    }

    public function me(Request $request)
    {
        $u = $request->user();

        return response()->json(
            $this->buildUserPayload($u)
        );
    }

    public function logout(Request $request)
    {
        $user  = $request->user();
        $token = $user?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            // Bearer token flow
            $token->delete();
            return response()->json(['message' => 'Logged out (token revoked)']);
        }

        if ($token instanceof TransientToken) {
            auth()->guard('web')->logout();
            $request->session()?->invalidate();
            $request->session()?->regenerateToken();
            return response()->json(['message' => 'Logged out (cookie mode)']);
        }

        // No token present
        return response()->json(['message' => 'No active token'], 200);
    }

    public function logoutAll(Request $request)
    {
        $user = $request->user();
        if ($user) {
            // Revoke all stored tokens
            $user->tokens()->delete();
        }
        auth()->guard('web')->logout();
        $request->session()?->invalidate();
        $request->session()?->regenerateToken();

        return response()->json(['message' => 'Logged out from all devices']);
    }

    /**
     * Build user payload for responses.
     *
     * Keeps old keys (id, username, name, email, auth, dn)
     * and adds:
     * - kode_department
     * - roles: [ "SYSADMIN", ... ]
     * - permissions: [ { menu_kode: "ASSETS", actions: ["R","C",...] }, ... ]
     */
    private function buildUserPayload(User $user, array $extra = []): array
    {
        // Role codes for this user
        $roles = $user->roles()
            ->pluck('kode')
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Aggregate actions per menu from master_role_menu
        $permissionsMap = [];

        if (! empty($roles)) {
            MasterRoleMenu::query()
                ->whereIn('role_kode', $roles)
                ->where('status', true)
                ->get(['menu_kode', 'actions'])
                ->each(function (MasterRoleMenu $rm) use (&$permissionsMap) {
                    $menu = $rm->menu_kode;
                    if (! isset($permissionsMap[$menu])) {
                        $permissionsMap[$menu] = [];
                    }

                    $actions = is_array($rm->actions) ? $rm->actions : [];
                    foreach ($actions as $act) {
                        if ($act !== null && ! in_array($act, $permissionsMap[$menu], true)) {
                            $permissionsMap[$menu][] = $act;
                        }
                    }
                });
        }

        // Normalize to array of { menu_kode, actions[] }
        $permissions = [];
        foreach ($permissionsMap as $menuKode => $actions) {
            sort($actions);
            $permissions[] = [
                'menu_kode' => $menuKode,
                'actions'   => array_values($actions),
            ];
        }

        // Base user fields (keep existing ones)
        $base = [
            'id'              => $user->id,
            'username'        => $user->username,
            'name'            => $user->name,
            'email'           => $user->email,
            'kode_department' => $user->kode_department,
            'roles'           => $roles,
            'permissions'     => $permissions,
        ];

        // Allow caller to inject extra keys like auth, dn
        return array_merge($base, $extra);
    }
}
