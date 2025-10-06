<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LdapAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

        // 1) Static admin short-circuit
        $staticUser = Str::lower((string) config('auth.static_admin.username', ''));
        $staticPass = (string) config('auth.static_admin.password', '');
        if (
            $staticUser !== '' && $staticPass !== '' &&
            hash_equals($staticUser, $username) && hash_equals($staticPass, $password)
        ) {

            $user = User::firstOrCreate(
                ['username' => 'admin'],
                ['name' => 'Administrator', 'email' => 'admin@example.com', 'password' => Hash::make(Str::random(32))]
            );

            $token = $user->createToken($device)->plainTextToken;
            RateLimiter::clear($key);

            return response()->json([
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user' => [
                    'id'       => $user->id,
                    'username' => $user->username,
                    'name'     => $user->name,
                    'email'    => $user->email,
                    'auth'     => 'static',
                ],
            ], 201);
        }

        // 2) LDAP
        $host    = config('ldap.host');
        $port    = (int) config('ldap.port', 389);
        $baseDn  = (string) config('ldap.base_dn');
        $roDn    = config('ldap.bind_dn');
        $roPass  = config('ldap.bind_pass');
        $timeout = (int) config('ldap.timeout', 5);

        $userDn = "uid={$username},{$baseDn}";
        $ok     = $host && $baseDn && $this->ldap->bindDn($host, $port, $userDn, $password, $timeout);

        if (!$ok && $host && $baseDn) {
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

            $user = User::updateOrCreate(
                ['username' => $username],                         // <-- username key
                ['name' => $cn, 'email' => $mail, 'password' => Hash::make(Str::random(32))]
            );

            $token = $user->createToken($device)->plainTextToken;
            RateLimiter::clear($key);

            return response()->json([
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user' => [
                    'id'       => $user->id,
                    'username' => $user->username,
                    'name'     => $user->name,
                    'email'    => $user->email,
                    'auth'     => 'ldap',
                    'dn'       => $userDn,
                ],
            ], 201);
        }
        return response()->json(['message' => 'Invalid credentials.'], 422);
    }

    public function me(Request $request)
    {
        $u = $request->user();
        return response()->json([
            'id'       => $u->id,
            'username' => $u->username,
            'name'     => $u->name,
            'email'    => $u->email,
        ]);
    }

    public function logout(Request $request)
    {
        $user  = $request->user(); // may be null if not authenticated
        $token = $user?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            // Bearer token flow
            $token->delete();
            return response()->json(['message' => 'Logged out (token revoked)']);
        }

        if ($token instanceof TransientToken) {
            // Cookie (SPA) mode – token isn’t stored, nothing to delete
            // Destroy session/cookie if you’re mixing guards:
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
        // Also clear session if cookie mode was used:
        auth()->guard('web')->logout();
        $request->session()?->invalidate();
        $request->session()?->regenerateToken();

        return response()->json(['message' => 'Logged out from all devices']);
    }
}
