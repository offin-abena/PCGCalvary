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
        $middleware->validateCsrfTokens(except: ['*']);

        $middleware->alias([
            'church.auth' => \App\Http\Middleware\ChurchAuthenticate::class,
            'church.csrf' => \App\Http\Middleware\ChurchCsrf::class,
            'church.access' => \App\Http\Middleware\ChurchModuleAccess::class,
            'church.security' => \App\Http\Middleware\ChurchSecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
