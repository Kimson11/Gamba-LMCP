<?php

namespace App\Services;

use App\Enums\ApprovalEventType;
use App\Enums\ApprovalStatus;
use App\Models\ApprovalEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Append-only approval event stream service.
 *
 * This service owns all workflow transition rules and writes one immutable
 * ApprovalEvent row per transition.
 *
 * Supported transitions:
 *   - (none)               -> submitted  (status: pending_approval)
 *   - pending_approval     -> approved   (status: approved)
 *   - pending_approval     -> rejected   (status: rejected)
 *   - approved             -> reversed   (status: reversed)
 *
 * Any other transition raises InvalidArgumentException.
 */
class ApprovalEventStream
{
    public function __construct(
        private readonly Request $request,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Submit an entity for approval.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function submit(
        string $entityType,
        int $entityId,
        array $metadata = [],
        ?User $actor = null,
    ): ApprovalEvent {
        $latest = $this->latest($entityType, $entityId);

        // Complex rule summary:
        // Submit is allowed for brand-new entities and also after terminal states
        // (rejected/reversed) to support re-submission workflows.
        if ($latest !== null && ! in_array($latest->current_status, [ApprovalStatus::Rejected, ApprovalStatus::Reversed], true)) {
            throw new InvalidArgumentException('Submit is only allowed for new, rejected, or reversed entities.');
        }

        return $this->recordEvent(
            entityType: $entityType,
            entityId: $entityId,
            eventType: ApprovalEventType::Submitted,
            nextStatus: ApprovalStatus::PendingApproval,
            actor: $actor,
            reason: null,
            metadata: $metadata,
        );
    }

    /**
     * Approve a pending entity.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function approve(
        string $entityType,
        int $entityId,
        array $metadata = [],
        ?User $actor = null,
    ): ApprovalEvent {
        $this->assertLatestStatus($entityType, $entityId, ApprovalStatus::PendingApproval, 'Approve');

        return $this->recordEvent(
            entityType: $entityType,
            entityId: $entityId,
            eventType: ApprovalEventType::Approved,
            nextStatus: ApprovalStatus::Approved,
            actor: $actor,
            reason: null,
            metadata: $metadata,
        );
    }

    /**
     * Reject a pending entity.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function reject(
        string $entityType,
        int $entityId,
        string $reason,
        array $metadata = [],
        ?User $actor = null,
    ): ApprovalEvent {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A reason is required when rejecting.');
        }

        $this->assertLatestStatus($entityType, $entityId, ApprovalStatus::PendingApproval, 'Reject');

        return $this->recordEvent(
            entityType: $entityType,
            entityId: $entityId,
            eventType: ApprovalEventType::Rejected,
            nextStatus: ApprovalStatus::Rejected,
            actor: $actor,
            reason: $reason,
            metadata: $metadata,
        );
    }

    /**
     * Reverse a previously approved entity.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function reverse(
        string $entityType,
        int $entityId,
        string $reason,
        array $metadata = [],
        ?User $actor = null,
    ): ApprovalEvent {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A reason is required when reversing.');
        }

        $this->assertLatestStatus($entityType, $entityId, ApprovalStatus::Approved, 'Reverse');

        return $this->recordEvent(
            entityType: $entityType,
            entityId: $entityId,
            eventType: ApprovalEventType::Reversed,
            nextStatus: ApprovalStatus::Reversed,
            actor: $actor,
            reason: $reason,
            metadata: $metadata,
        );
    }

    /**
     * Return the latest event row for a given entity.
     */
    public function latest(string $entityType, int $entityId): ?ApprovalEvent
    {
        return ApprovalEvent::query()
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->latest('id')
            ->first();
    }

    /**
     * Return all events (oldest -> newest) for a given entity.
     */
    public function timeline(string $entityType, int $entityId)
    {
        return ApprovalEvent::query()
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('id')
            ->get();
    }

    /**
     * Validate current state before applying a transition.
     */
    private function assertLatestStatus(
        string $entityType,
        int $entityId,
        ApprovalStatus $expected,
        string $action,
    ): void {
        $latest = $this->latest($entityType, $entityId);

        if ($latest === null) {
            throw new InvalidArgumentException($action.' requires a submitted entity.');
        }

        if ($latest->current_status !== $expected) {
            throw new InvalidArgumentException(
                $action.' is only allowed when current status is '.$expected->value.'.'
            );
        }
    }

    /**
     * Complex section summary:
     * This method wraps event persistence and audit logging in one database
     * transaction so we never write an approval event without its companion audit row.
     *
     * Example output row:
     *   event_type='approved', current_status='approved', actor_role='coop_admin'
     *
     * @param  array<string, mixed>  $metadata
     */
    private function recordEvent(
        string $entityType,
        int $entityId,
        ApprovalEventType $eventType,
        ApprovalStatus $nextStatus,
        ?User $actor,
        ?string $reason,
        array $metadata,
    ): ApprovalEvent {
        return DB::transaction(function () use ($entityType, $entityId, $eventType, $nextStatus, $actor, $reason, $metadata): ApprovalEvent {
            $resolvedActor = $actor ?? $this->request->user();

            $event = ApprovalEvent::query()->create([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'event_type' => $eventType,
                'current_status' => $nextStatus,
                'actor_id' => $resolvedActor?->id,
                'actor_role' => $resolvedActor?->role?->value,
                'reason' => $reason,
                'metadata' => $metadata !== [] ? $metadata : null,
                'correlation_id' => $this->request->attributes->get('correlation_id'),
            ]);

            // Companion audit event for compliance traceability.
            $this->auditLogger->record(
                action: 'approval.'.$eventType->value,
                subject: $event,
                actor: $resolvedActor,
                context: [
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'new_status' => $nextStatus->value,
                    'reason' => $reason,
                ],
            );

            return $event;
        });
    }
}
