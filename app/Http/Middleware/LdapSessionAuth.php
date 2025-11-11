<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class LdapSessionAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('ldap.login');
        }

        if (! $request->session()->has('ldap_user')) {
            $u = Auth::user();
            $request->session()->put('ldap_user', [
                'username' => $u->username ?? null,
                'name'     => $u->name ?? null,
                'email'    => $u->email ?? null,
                'ou'       => $u->ou ?? null,
            ]);
        }

        return $next($request);
    }
}
