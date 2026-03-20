<?php

use App\Http\Middleware\SecurityHeaders;
use App\Modules\Auth\Services\AuthService;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        App\Providers\ModuleServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        // API middleware stack
        $middleware->api(prepend: [
            HandleCors::class,
            SecurityHeaders::class,          // ← Security headers mới
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
        ]);

        // Không mã hóa cookie auth
        $middleware->encryptCookies(except: [
            AuthService::TOKEN_COOKIE,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
