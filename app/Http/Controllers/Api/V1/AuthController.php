<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use App\Support\ScopeAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Handles Sanctum token-based authentication for the API.
 *
 * Login flow:
 *   POST /api/v1/auth/login
 *     → validate credentials
 *     → create Sanctum token named after the device
 *     → record 'user.login' audit event
 *     → return token + user + role in standard API envelope
 *
 * Logout flow:
 *   POST /api/v1/auth/logout  (requires auth:sanctum)
 *     → revoke the current token only (not all tokens)
 *     → record 'user.logout' audit event
 *     → return success envelope
 */
class AuthController extends Controller
{
    public function __construct(
        // Injected so every auth event automatically carries correlation_id.
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Authenticate the user and return a Sanctum API token.
     *
     * Request body (JSON):
     *   { "email": "...", "password": "...", "device_name": "Gamba Android" }
     *
     * Success response (201):
     *   {
     *     "data": {
     *       "token": "1|abc...",      ← use as Bearer token in Authorization header
     *       "token_type": "Bearer",
     *       "user": {
     *         "id": 42,
     *         "name": "Jane Farmer",
     *         "email": "jane@example.com",
     *         "role": "member"         ← UserRole enum value string
     *       }
     *     },
     *     "meta": { "request_id": "...", "timestamp": "..." }
     *   }
     *
     * Failure (401):
     *   { "message": "Invalid credentials.", "code": "invalid_credentials", ... }
     */
    public function login(LoginRequest $request, ScopeAccess $scopeAccess): JsonResponse
    {
        // Attempt to authenticate using the validated email and password.
        // Auth::attempt() hashes the password and compares against the stored hash.
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            // Do NOT reveal whether the email or password was wrong specifically —
            // a generic message prevents user-enumeration attacks.
            return ApiResponse::error(
                message: 'Invalid credentials.',
                code: 'invalid_credentials',
                status: 401,
            );
        }

        $user = Auth::user();

        // Create a Sanctum personal access token scoped to this device.
        // The device_name is the human-readable label (e.g. 'Gamba Android').
        // No ability scopes are set here; role-based middleware handles access control.
        $token = $user->createToken($request->string('device_name'))->plainTextToken;

        // Record the login event in the audit log.
        // This ties the login to the request's correlation_id automatically.
        $this->auditLogger->record(
            action: 'user.login',
            subject: $user,
            actor: $user,
            context: [
                'device_name' => $request->string('device_name')->toString(),
                'ip_address' => $request->ip(),
            ],
        );

        return ApiResponse::success([
            'token' => $token,           // Send as Authorization: Bearer <token>
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                // Return the role's string value (e.g. 'member') so the
                // Flutter client can make role-aware routing decisions.
                'role' => $user->role->value,
            ],
            'scopes' => $scopeAccess->summary($user),
            'linked_member' => $this->serializeLinkedMember($scopeAccess->linkedMember($user)),
            'security' => [
                'mfa_enabled' => (bool) $user->mfa_enabled,
                'privileged_session_required' => $user->isPrivileged(),
            ],
        ], status: 201);
    }

    /**
     * Return the current authenticated user, scopes, and session baseline.
     */
    public function me(Request $request, ScopeAccess $scopeAccess): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user?->currentAccessToken();

        return ApiResponse::success([
            'user' => [
                'id' => $user?->id,
                'name' => $user?->name,
                'email' => $user?->email,
                'role' => $user?->role?->value,
            ],
            'scopes' => $scopeAccess->summary($user),
            'linked_member' => $this->serializeLinkedMember($scopeAccess->linkedMember($user)),
            'token' => [
                'id' => $currentToken?->id,
                'name' => $currentToken?->name,
                'last_used_at' => $currentToken?->last_used_at?->toIso8601String(),
            ],
            'security' => [
                'mfa_enabled' => (bool) $user?->mfa_enabled,
                'privileged_session_required' => (bool) $user?->isPrivileged(),
                'trusted_device_count' => $user?->trustedDevices()->count() ?? 0,
            ],
        ]);
    }

    /**
     * Revoke the currently authenticated token (logout from this device only).
     *
     * This does NOT log out all devices. The user can manage other tokens
     * separately via their profile.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Record the logout before revoking to ensure audit integrity
        // (after revoke, the user object is still available in this request).
        $this->auditLogger->record(
            action: 'user.logout',
            subject: $user,
            actor: $user,
        );

        // currentAccessToken() returns the Sanctum token model that authenticated
        // this request. Calling delete() removes only that one token row,
        // leaving any other device tokens intact.
        /** @var PersonalAccessToken $token */
        $token = $request->user()->currentAccessToken();
        $token->delete();

        return ApiResponse::success(['message' => 'Logged out successfully.']);
    }

    /**
     * Normalize the linked member payload returned during session bootstrap.
     *
     * @return array<string, mixed>|null
     */
    private function serializeLinkedMember(?object $member): ?array
    {
        if ($member === null) {
            return null;
        }

        return [
            'id' => $member->id,
            'cooperative_id' => $member->cooperative_id,
            'cluster_id' => $member->cluster_id,
            'member_number' => $member->member_number,
            'status' => $member->status,
        ];
    }
}
