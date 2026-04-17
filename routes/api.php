<?php

use App\Http\Controllers\Api\V1\ApprovalEventController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClusterController;
use App\Http\Controllers\Api\V1\CooperativeController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\MilkProductionController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\SystemController;
use Illuminate\Support\Facades\Route;

/**
 * LMCP API v1 Routes
 *
 * All routes are prefixed with /api/v1 automatically by Laravel's api routing.
 *
 * Route groups:
 *   - Public          no authentication required (login)
 *   - Authenticated   requires valid Sanctum token (auth:sanctum)
 *   - Idempotent      write endpoints that require Idempotency-Key header
 */
Route::prefix('v1')->group(function (): void {
    // ── Public ──────────────────────────────────────────────────────────

    // Health-check endpoint — no auth, no idempotency key needed.
    // Used by Flutter app to verify API reachability before login.
    Route::get('/system/ping', [SystemController::class, 'ping']);

    // Login: validates credentials and returns a Sanctum token.
    // The token must be sent as 'Authorization: Bearer <token>' on all
    // subsequent authenticated requests.
    Route::post('/auth/login', [AuthController::class, 'login']);

    // ── Authenticated ───────────────────────────────────────────────────

    // All routes inside this group require a valid Sanctum token.
    // If the token is missing or expired, Laravel returns 401 automatically.
    Route::middleware('auth:sanctum')->group(function (): void {
        // Revoke the current device's token (single-device logout).
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Sync reconciliation endpoints.
        // POST /sync/batch processes offline replay items in request order.
        Route::post('/sync/batch', [SyncController::class, 'batch']);
        // GET /sync/status returns recent reconciliation outcomes for diagnostics.
        Route::get('/sync/status', [SyncController::class, 'status']);
        // GET /sync/conflicts lists unresolved conflicts for current actor scope.
        Route::get('/sync/conflicts', [SyncController::class, 'conflicts']);
        // GET /sync/conflicts/{id} returns one conflict detail and options.
        Route::get('/sync/conflicts/{syncReplayItem}', [SyncController::class, 'conflict']);
        // GET /sync/conflicts/{id}/history returns append-only action timeline.
        Route::get('/sync/conflicts/{syncReplayItem}/history', [SyncController::class, 'conflictHistory']);
        // POST /sync/conflicts/{id}/resolve executes a supported resolution action.
        Route::post('/sync/conflicts/{syncReplayItem}/resolve', [SyncController::class, 'resolveConflict']);
        // POST /sync/conflicts/{id}/notes adds reviewer notes to the timeline.
        Route::post('/sync/conflicts/{syncReplayItem}/notes', [SyncController::class, 'addConflictNote']);

        // Cooperative structure foundation endpoints.
        // Read endpoints available to operational and governance roles.
        Route::middleware('role:system_admin,country_admin,coop_admin,cluster_supervisor')->group(function (): void {
            Route::get('/cooperatives', [CooperativeController::class, 'index']);
            Route::get('/cooperatives/{cooperative}/clusters', [ClusterController::class, 'indexByCooperative']);
            Route::get('/clusters/{cluster}/members', [MemberController::class, 'indexByCluster']);
            Route::get('/members/{member}/assignments', [MemberController::class, 'assignments']);
            Route::get('/member-assignments/history', [MemberController::class, 'assignmentHistory']);
            Route::get('/member-assignments/pending', [MemberController::class, 'pendingAssignments']);
            Route::get('/clusters/{cluster}/milk-production-logs', [MilkProductionController::class, 'indexByCluster']);
            Route::get('/clusters/{cluster}/milk-production-totals/daily', [MilkProductionController::class, 'dailyTotalsByCluster']);
            Route::get('/members/{member}/milk-production-totals/daily', [MilkProductionController::class, 'dailyTotalsByMember']);
        });

        // Write endpoints require governance roles and idempotency protection.
        Route::middleware([
            'role:system_admin,country_admin,coop_admin',
            'idempotency',
        ])->group(function (): void {
            Route::post('/cooperatives', [CooperativeController::class, 'store']);
            Route::post('/clusters', [ClusterController::class, 'store']);
            Route::post('/members', [MemberController::class, 'store']);
            Route::post('/members/{member}/assign-cluster', [MemberController::class, 'assignCluster']);
            Route::post('/member-assignments/{memberAssignment}/approve', [MemberController::class, 'approveAssignment']);
            Route::post('/member-assignments/{memberAssignment}/reject', [MemberController::class, 'rejectAssignment']);
        });

        Route::middleware([
            'role:member,cluster_supervisor,coop_admin,country_admin,system_admin',
            'idempotency',
        ])->group(function (): void {
            Route::post('/milk-production-logs', [MilkProductionController::class, 'store']);
        });

        // Approval event timeline read endpoint.
        Route::get('/approvals/{entityType}/{entityId}/events', [ApprovalEventController::class, 'index']);

        // Submit endpoint: any authenticated user can submit approval-governed entities.
        Route::middleware('idempotency')->group(function (): void {
            Route::post('/approvals/{entityType}/{entityId}/submit', [ApprovalEventController::class, 'submit']);
        });

        // Privileged transitions (approve/reject/reverse) are restricted to governance roles.
        // Roles used here come from the UserRole enum values.
        Route::middleware([
            'role:system_admin,country_admin,coop_admin,finance_officer,treasurer,marketplace_manager',
            'idempotency',
        ])->group(function (): void {
            Route::post('/approvals/{entityType}/{entityId}/approve', [ApprovalEventController::class, 'approve']);
            Route::post('/approvals/{entityType}/{entityId}/reject', [ApprovalEventController::class, 'reject']);
            Route::post('/approvals/{entityType}/{entityId}/reverse', [ApprovalEventController::class, 'reverse']);
        });
    });

    // ── Idempotent write endpoints ───────────────────────────────────────

    // All routes in this group require the Idempotency-Key header.
    // The middleware replays the original response if the key is reused.
    Route::middleware('idempotency')->group(function (): void {
        // Example idempotent write — used to verify the idempotency system.
        Route::post('/system/idempotent-echo', [SystemController::class, 'idempotentEcho']);
    });
});
