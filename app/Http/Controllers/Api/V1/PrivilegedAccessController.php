<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TrustedDevice;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Manages privileged-access baseline controls (MFA + trusted devices + token revocation).
 */
class PrivilegedAccessController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Return current privileged-access status for the authenticated user.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::success([
            'mfa_enabled' => (bool) $user->mfa_enabled,
            'mfa_secret_rotated_at' => $user->mfa_secret_rotated_at?->toIso8601String(),
            'trusted_devices' => $user->trustedDevices()
                ->orderByDesc('last_used_at')
                ->get([
                    'id',
                    'device_name',
                    'device_fingerprint',
                    'last_used_at',
                    'expires_at',
                    'revoked_at',
                    'created_at',
                ]),
        ]);
    }

    /**
     * Enable MFA by storing an encrypted secret and rotated timestamp.
     */
    public function enableMfa(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'secret' => ['required', 'string', 'min:16', 'max:255'],
        ]);

        $user = $request->user();

        $user->forceFill([
            'mfa_enabled' => true,
            'mfa_secret_encrypted' => encrypt($validated['secret']),
            'mfa_secret_rotated_at' => now(),
        ])->save();

        $this->auditLogger->record(
            action: 'security.mfa_enabled',
            subject: $user,
            actor: $user,
        );

        return ApiResponse::success([
            'message' => 'MFA enabled successfully.',
            'mfa_enabled' => true,
        ]);
    }

    /**
     * Rotate MFA secret while keeping MFA enabled.
     */
    public function rotateMfaSecret(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'secret' => ['required', 'string', 'min:16', 'max:255'],
        ]);

        $user = $request->user();

        if (! $user->mfa_enabled) {
            throw ValidationException::withMessages([
                'secret' => ['MFA must be enabled before rotating the secret.'],
            ]);
        }

        $user->forceFill([
            'mfa_secret_encrypted' => encrypt($validated['secret']),
            'mfa_secret_rotated_at' => now(),
        ])->save();

        $this->auditLogger->record(
            action: 'security.mfa_secret_rotated',
            subject: $user,
            actor: $user,
        );

        return ApiResponse::success(['message' => 'MFA secret rotated successfully.']);
    }

    /**
     * Disable MFA and clear the stored encrypted secret material.
     */
    public function disableMfa(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'mfa_enabled' => false,
            'mfa_secret_encrypted' => null,
            'mfa_secret_rotated_at' => null,
        ])->save();

        $this->auditLogger->record(
            action: 'security.mfa_disabled',
            subject: $user,
            actor: $user,
        );

        return ApiResponse::success(['message' => 'MFA disabled successfully.']);
    }

    /**
     * Register or refresh a trusted device for the authenticated user.
     */
    public function enrollTrustedDevice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_fingerprint' => ['required', 'string', 'min:16', 'max:255'],
            'device_name' => ['required', 'string', 'max:120'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $user = $request->user();

        $trustedDevice = TrustedDevice::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'device_fingerprint' => $validated['device_fingerprint'],
            ],
            [
                'device_name' => $validated['device_name'],
                'expires_at' => $validated['expires_at'] ?? null,
                'last_used_at' => now(),
                'revoked_at' => null,
            ],
        );

        $this->auditLogger->record(
            action: 'security.trusted_device_enrolled',
            subject: $user,
            actor: $user,
            context: [
                'trusted_device_id' => $trustedDevice->id,
                'device_name' => $trustedDevice->device_name,
            ],
        );

        return ApiResponse::success([
            'id' => $trustedDevice->id,
            'device_name' => $trustedDevice->device_name,
            'device_fingerprint' => $trustedDevice->device_fingerprint,
            'expires_at' => $trustedDevice->expires_at?->toIso8601String(),
            'revoked_at' => $trustedDevice->revoked_at?->toIso8601String(),
        ], status: 201);
    }

    /**
     * Revoke a trusted device owned by the authenticated user.
     */
    public function revokeTrustedDevice(Request $request, TrustedDevice $trustedDevice): JsonResponse
    {
        $user = $request->user();

        if ($trustedDevice->user_id !== $user->id) {
            return ApiResponse::error(
                message: 'You are not authorized to revoke this trusted device.',
                code: 'permission_denied',
                status: 403,
            );
        }

        $trustedDevice->forceFill([
            'revoked_at' => now(),
        ])->save();

        $this->auditLogger->record(
            action: 'security.trusted_device_revoked',
            subject: $user,
            actor: $user,
            context: [
                'trusted_device_id' => $trustedDevice->id,
            ],
        );

        return ApiResponse::success(['message' => 'Trusted device revoked successfully.']);
    }

    /**
     * Revoke all Sanctum tokens except the token currently used by this request.
     */
    public function revokeOtherSessions(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        $deletedCount = $user->tokens()
            ->where('id', '!=', $currentToken?->id)
            ->delete();

        $this->auditLogger->record(
            action: 'security.sessions_revoked',
            subject: $user,
            actor: $user,
            context: [
                'revoked_tokens_count' => $deletedCount,
            ],
        );

        return ApiResponse::success([
            'message' => 'Other active sessions revoked successfully.',
            'revoked_tokens_count' => $deletedCount,
        ]);
    }
}
