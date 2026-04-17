<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware that restricts access to specific user roles.
 *
 * Apply to a route by listing the allowed roles as middleware parameters:
 *
 *   Route::middleware('role:coop_admin,system_admin')->group(...)
 *
 * The middleware checks that:
 *   1. The user is authenticated via Sanctum (handled by auth:sanctum first)
 *   2. The user's role matches at least one of the allowed values
 *
 * Example: role:member,coop_admin  → allows UserRole::Member OR UserRole::CoopAdmin
 */
class RequireRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     * @param  string  ...$roles  One or more UserRole enum values (e.g. 'member', 'coop_admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // If no user is authenticated at this point, auth:sanctum should have
        // already rejected the request. This is a safety net.
        if ($user === null) {
            return ApiResponse::error(
                message: 'Unauthenticated.',
                code: 'unauthenticated',
                status: 401,
            );
        }

        // Convert the raw string list (e.g. ['member', 'coop_admin']) to
        // UserRole enum cases so we can use strict equality checks.
        // tryFrom() returns null for unknown values — we filter those out.
        $allowedRoles = array_filter(
            array_map(fn (string $r) => UserRole::tryFrom($r), $roles),
            fn ($role) => $role !== null,
        );

        // Check whether the user's role is in the allowed list.
        // in_array with strict=true uses === for enum comparison.
        if (! in_array($user->role, $allowedRoles, strict: true)) {
            return ApiResponse::error(
                message: 'You are not authorized to perform this action.',
                code: 'permission_denied',
                status: 403,
            );
        }

        return $next($request);
    }
}
