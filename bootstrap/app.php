<?php

use App\Http\Middleware\EnsureHasAction;
use App\Http\Middleware\LdapSessionAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\StartSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'ldap.session' => LdapSessionAuth::class,
            'role.action' => EnsureHasAction::class,
        ]);
        
        $middleware->append(StartSession::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
