<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'organization' => \App\Http\Middleware\SetCurrentOrganization::class,
            'super_admin' => \App\Http\Middleware\SuperAdmin::class,
            'owner' => \App\Http\Middleware\OwnerOnly::class,
        ]);

        // Agregar el middleware de organización al grupo web (después de auth)
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\SetCurrentOrganization::class,
        ]);

        // Excluir rutas de verificación CSRF (para uploads directos a Spaces y webhooks)
        $middleware->validateCsrfTokens(except: [
            'videos',
            'videos/*',
            'api/upload/*',
            'webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
