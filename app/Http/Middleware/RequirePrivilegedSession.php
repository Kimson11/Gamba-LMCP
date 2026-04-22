<?php

namespace App\Http\Middleware;

use App\Models\TrustedDevice;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces elevated-session proof for privileged actors.
 *
 * For privileged roles only, this middleware requires:
 * - mfa_enabled on the user account
 * - X-MFA-Verified: true request header
 * - X-Trusted-Device-Id header pointing to an active trusted device
 */
class RequirePrivilegedSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isPrivileged()) {
            return $next($request);
        }

        if (! $user->mfa_enabled) {
            return ApiResponse::error(
                message: 'Privileged session verification is required.',
                code: 'privileged_session_required',
                status: 403,
            );
        }

        if ($request->header('X-MFA-Verified') !== 'true') {
            return ApiResponse::error(
                message: 'MFA proof is required for privileged actions.',
                code: 'privileged_session_required',
                status: 403,
            );
        }

        $trustedDeviceId = $request->header('X-Trusted-Device-Id');

        if (! is_numeric($trustedDeviceId)) {
            return ApiResponse::error(
                message: 'Trusted device proof is required for privileged actions.',
                code: 'privileged_session_required',
                status: 403,
            );
        }

        /** @var TrustedDevice|null $trustedDevice */
        $trustedDevice = TrustedDevice::query()
            ->where('id', (int) $trustedDeviceId)
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->first();

        if ($trustedDevice === null) {
            return ApiResponse::error(
                message: 'Trusted device proof is invalid or expired.',
                code: 'privileged_session_required',
                status: 403,
            );
        }

        $trustedDevice->update(['last_used_at' => now()]);

        return $next($request);
    }
}
