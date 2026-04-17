<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ApprovalEventStream;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * API endpoints for approval workflow event transitions.
 */
class ApprovalEventController extends Controller
{
    public function __construct(
        private readonly ApprovalEventStream $approvalEventStream,
    ) {}

    /**
     * Return the full event timeline for one entity.
     */
    public function index(string $entityType, int $entityId): JsonResponse
    {
        $events = $this->approvalEventStream->timeline($entityType, $entityId);

        return ApiResponse::success([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'events' => $events->map(function ($event): array {
                return [
                    'id' => $event->id,
                    'event_type' => $event->event_type->value,
                    'current_status' => $event->current_status->value,
                    'actor_id' => $event->actor_id,
                    'actor_role' => $event->actor_role,
                    'reason' => $event->reason,
                    'metadata' => $event->metadata,
                    'created_at' => $event->created_at?->toIso8601String(),
                ];
            })->all(),
        ]);
    }

    /**
     * Submit an entity into pending approval status.
     */
    public function submit(Request $request, string $entityType, int $entityId): JsonResponse
    {
        $validated = $request->validate([
            'metadata' => ['sometimes', 'array'],
        ]);

        return $this->handleTransition(function () use ($request, $entityType, $entityId, $validated) {
            return $this->approvalEventStream->submit(
                entityType: $entityType,
                entityId: $entityId,
                metadata: $validated['metadata'] ?? [],
                actor: $request->user(),
            );
        });
    }

    /**
     * Approve a pending entity.
     */
    public function approve(Request $request, string $entityType, int $entityId): JsonResponse
    {
        $validated = $request->validate([
            'metadata' => ['sometimes', 'array'],
        ]);

        return $this->handleTransition(function () use ($request, $entityType, $entityId, $validated) {
            return $this->approvalEventStream->approve(
                entityType: $entityType,
                entityId: $entityId,
                metadata: $validated['metadata'] ?? [],
                actor: $request->user(),
            );
        });
    }

    /**
     * Reject a pending entity. Reject requires a human reason.
     */
    public function reject(Request $request, string $entityType, int $entityId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'metadata' => ['sometimes', 'array'],
        ]);

        return $this->handleTransition(function () use ($request, $entityType, $entityId, $validated) {
            return $this->approvalEventStream->reject(
                entityType: $entityType,
                entityId: $entityId,
                reason: $validated['reason'],
                metadata: $validated['metadata'] ?? [],
                actor: $request->user(),
            );
        });
    }

    /**
     * Reverse an approved entity. Reverse requires a reason.
     */
    public function reverse(Request $request, string $entityType, int $entityId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'metadata' => ['sometimes', 'array'],
        ]);

        return $this->handleTransition(function () use ($request, $entityType, $entityId, $validated) {
            return $this->approvalEventStream->reverse(
                entityType: $entityType,
                entityId: $entityId,
                reason: $validated['reason'],
                metadata: $validated['metadata'] ?? [],
                actor: $request->user(),
            );
        });
    }

    /**
     * Complex section summary:
     * Wrap transition execution so all transition endpoints share one
     * consistent success/error response shape.
     */
    private function handleTransition(callable $transition): JsonResponse
    {
        try {
            $event = $transition();

            return ApiResponse::success([
                'id' => $event->id,
                'entity_type' => $event->entity_type,
                'entity_id' => $event->entity_id,
                'event_type' => $event->event_type->value,
                'current_status' => $event->current_status->value,
                'reason' => $event->reason,
                'metadata' => $event->metadata,
                'created_at' => $event->created_at?->toIso8601String(),
            ], 201);
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error(
                message: $exception->getMessage(),
                code: 'invalid_approval_transition',
                status: 422,
            );
        }
    }
}
