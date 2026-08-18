<?php

use App\Http\Middleware\EnsureCfmManagementAccess;
use App\Http\Middleware\EnsureCfmPortalAccess;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\LoadAuthenticatedUserProfile;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            LoadAuthenticatedUserProfile::class,
        ]);

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'cfm.management' => EnsureCfmManagementAccess::class,
            'cfm.portal' => EnsureCfmPortalAccess::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
