<?php

use App\Http\Middleware\AttachRequestContext;
use App\Http\Middleware\HandleIdempotency;
use App\Http\Middleware\RequireRole;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AttachRequestContext::class);
        $middleware->alias([
            'idempotency' => HandleIdempotency::class,
            // role:<role1>,<role2>  — restrict access to specific UserRole values.
            // Example usage on a route: ->middleware('role:coop_admin,system_admin')
            'role' => RequireRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                message: 'Validation failed.',
                code: 'validation_failed',
                status: 422,
                errors: $exception->errors(),
            );
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                message: 'You are not authorized to perform this action.',
                code: 'permission_denied',
                status: 403,
            );
        });
    })->create();
