<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasAction
{
    /**
     * Usage examples:
     *  ->middleware('role.action:MASTER_DATA,R')
     *  ->middleware('role.action:MASTER_DATA,C,U')   // C OR U
     */
    public function handle(Request $request, Closure $next, string $menuKode, ...$actions): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        if (empty($actions)) {
            $actions = ['R'];
        }

        foreach ($actions as $action) {
            if ($user->hasAction($menuKode, $action)) {
                return $next($request);
            }
        }

        abort(403, 'You are not allowed to perform this action.');
    }
}
