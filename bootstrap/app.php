<?php

use App\Http\Middleware\BlockedUser;
use App\Http\Middleware\Sitemapped;
use App\Http\Middleware\TrackCouponCode;
use App\Http\Middleware\TrackReferralCode;
use App\Http\Middleware\UpdateUserLastSeenAt;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('web', [
            BlockedUser::class,
            UpdateUserLastSeenAt::class,
            TrackReferralCode::class,
            TrackCouponCode::class,
        ]);

        $middleware->alias([
            'sitemapped' => Sitemapped::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {})->create();
