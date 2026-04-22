<?php

use App\Http\Middleware\AttachRequestContext;
use App\Http\Middleware\EnsureAdminRole;
use App\Http\Middleware\HandleIdempotency;
use App\Http\Middleware\RequirePrivilegedSession;
use App\Http\Middleware\RequireRole;
use App\Http\Middleware\RequireWebPrivilegedSession;
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
            // privileged_session enforces MFA and trusted-device checks for privileged roles.
            'privileged_session' => RequirePrivilegedSession::class,
            'admin.role' => EnsureAdminRole::class,
            'web.privileged_session' => RequireWebPrivilegedSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                message: __('api.errors.validation_failed'),
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
                message: __('api.errors.permission_denied'),
                code: 'permission_denied',
                status: 403,
            );
        });
    })->create();
