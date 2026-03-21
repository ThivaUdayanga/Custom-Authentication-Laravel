<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AuthCheck;
use App\Http\Middleware\IsLogedIn;
use App\Http\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'authcheck' => AuthCheck::class,
            'islogedin' => IsLogedIn::class,
            'role' => RoleMiddleware::class,
        ]);
        // //temporary for csrf token verification, will be removed later
        // $middleware->validateCsrfTokens(except: [
        //     'api/auth/login',
        //     'api/auth/logout',
        //     'api/auth/me',
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();