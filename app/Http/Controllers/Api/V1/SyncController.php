<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SyncBatchProcessor;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Sync reconciliation endpoints.
 *
 * Endpoints:
 *  - POST /api/v1/sync/batch
 *  - GET  /api/v1/sync/status
 */
class SyncController extends Controller
{
    public function __construct(
        private readonly SyncBatchProcessor $syncBatchProcessor,
    ) {}

    /**
     * Process an offline replay batch.
     *
     * Expected request shape:
     * {
     *   "data": {
     *     "device_id": "android-2af1",
     *     "sent_at": "2026-03-15T08:10:00Z",
     *     "items": [
     *       {
     *         "client_request_id": "uuid",
     *         "request_type": "milk_log_create",
     *         "offline_class": "A",
     *         "occurred_at": "...",
     *         "payload": { ... }
     *       }
     *     ]
     *   }
     * }
     */
    public function batch(Request $request): JsonResponse
    {
        // Envelope validation is strict so the client can map validation errors to fields.
        $validated = $request->validate([
            'data' => ['required', 'array'],
            'data.device_id' => ['required', 'string', 'max:255'],
            'data.sent_at' => ['required', 'date'],
            'data.items' => ['required', 'array', 'min:1'],
            'data.items.*.client_request_id' => ['required', 'uuid'],
            'data.items.*.request_type' => ['required', 'string', 'max:255'],
            'data.items.*.offline_class' => ['required', 'string', 'in:A,B,C'],
            'data.items.*.occurred_at' => ['required', 'date'],
            'data.items.*.payload' => ['required', 'array'],
        ]);

        $actor = $request->user();

        $result = $this->syncBatchProcessor->processBatch(
            actor: $actor,
            items: $validated['data']['items'],
        );

        return ApiResponse::success($result);
    }

    /**
     * Return the authenticated user's recent sync reconciliation summary.
     */
    public function status(Request $request): JsonResponse
    {
        $actor = $request->user();

        $status = $this->syncBatchProcessor->status($actor);

        return ApiResponse::success($status);
    }

    /**
     * List unresolved conflicts available to the authenticated actor.
     */
    public function conflicts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'conflict_code' => ['nullable', 'string', 'max:100'],
            'min_age_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'priority_rank' => ['nullable', 'integer', 'in:1,2,3'],
            'sort_by' => ['nullable', 'string', 'in:priority,oldest,newest'],
        ]);

        $actor = $request->user();

        $conflicts = $this->syncBatchProcessor->conflicts(
            actor: $actor,
            perPage: (int) ($validated['per_page'] ?? 15),
            filters: [
                'conflict_code' => $validated['conflict_code'] ?? null,
                'min_age_hours' => isset($validated['min_age_hours']) ? (int) $validated['min_age_hours'] : null,
                'priority_rank' => isset($validated['priority_rank']) ? (int) $validated['priority_rank'] : null,
                'sort_by' => $validated['sort_by'] ?? null,
            ],
        );

        return ApiResponse::success($conflicts);
    }

    /**
     * Return detail for one unresolved conflict.
     */
    public function conflict(Request $request, int $syncReplayItem): JsonResponse
    {
        $actor = $request->user();

        try {
            $conflict = $this->syncBatchProcessor->conflictDetail(
                actor: $actor,
                conflictId: $syncReplayItem,
            );
        } catch (ModelNotFoundException) {
            return ApiResponse::error(
                code: 'not_found',
                message: 'Conflict row not found for the current actor scope.',
                status: 404,
            );
        }

        return ApiResponse::success($conflict);
    }

    /**
     * Execute a conflict-resolution action.
     */
    public function resolveConflict(Request $request, int $syncReplayItem): JsonResponse
    {
        $validated = $request->validate([
            'data' => ['required', 'array'],
            'data.action' => ['required', 'string', 'in:view_server_record,create_replacement_submission'],
            'data.reason' => ['nullable', 'string', 'max:2000'],
            'data.replacement_item' => ['nullable', 'array'],
            'data.replacement_item.client_request_id' => ['nullable', 'uuid'],
            'data.replacement_item.request_type' => ['nullable', 'string', 'max:255'],
            'data.replacement_item.offline_class' => ['nullable', 'string', 'in:A,B,C'],
            'data.replacement_item.occurred_at' => ['nullable', 'date'],
            'data.replacement_item.payload' => ['nullable', 'array'],
        ]);

        $actor = $request->user();

        try {
            $result = $this->syncBatchProcessor->resolveConflict(
                actor: $actor,
                conflictId: $syncReplayItem,
                action: (string) $validated['data']['action'],
                reason: isset($validated['data']['reason']) ? (string) $validated['data']['reason'] : null,
                replacementItem: $validated['data']['replacement_item'] ?? null,
            );
        } catch (ModelNotFoundException) {
            return ApiResponse::error(
                code: 'not_found',
                message: 'Conflict row not found for the current actor scope.',
                status: 404,
            );
        } catch (InvalidArgumentException $exception) {
            $normalizedMessage = strtolower($exception->getMessage());
            $status = str_contains($normalizedMessage, 'privileged') ? 403 : 422;
            $code = $status === 403 ? 'permission_denied' : 'validation_failed';

            return ApiResponse::error(
                code: $code,
                message: $exception->getMessage(),
                status: $status,
            );
        }

        return ApiResponse::success($result);
    }

    /**
     * Return append-only action history for one conflict.
     */
    public function conflictHistory(Request $request, int $syncReplayItem): JsonResponse
    {
        $actor = $request->user();

        try {
            $history = $this->syncBatchProcessor->conflictHistory(
                actor: $actor,
                conflictId: $syncReplayItem,
            );
        } catch (ModelNotFoundException) {
            return ApiResponse::error(
                code: 'not_found',
                message: 'Conflict row not found for the current actor scope.',
                status: 404,
            );
        }

        return ApiResponse::success($history);
    }

    /**
     * Add a reviewer note to one conflict timeline.
     */
    public function addConflictNote(Request $request, int $syncReplayItem): JsonResponse
    {
        $validated = $request->validate([
            'data' => ['required', 'array'],
            'data.note' => ['required', 'string', 'max:2000'],
        ]);

        $actor = $request->user();

        try {
            $action = $this->syncBatchProcessor->addConflictNote(
                actor: $actor,
                conflictId: $syncReplayItem,
                note: (string) $validated['data']['note'],
            );
        } catch (ModelNotFoundException) {
            return ApiResponse::error(
                code: 'not_found',
                message: 'Conflict row not found for the current actor scope.',
                status: 404,
            );
        } catch (InvalidArgumentException $exception) {
            $normalizedMessage = strtolower($exception->getMessage());
            $status = str_contains($normalizedMessage, 'reviewer') ? 403 : 422;
            $code = $status === 403 ? 'permission_denied' : 'validation_failed';

            return ApiResponse::error(
                code: $code,
                message: $exception->getMessage(),
                status: $status,
            );
        }

        return ApiResponse::success($action, 201);
    }
}
