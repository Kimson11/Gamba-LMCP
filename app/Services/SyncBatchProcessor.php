<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MilkProductionLog;
use App\Models\SyncConflictAction;
use App\Models\SyncReplayItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Processes offline replay items from /api/v1/sync/batch.
 *
 * Core contract guarantees enforced here:
 *  1) Per-item isolated transaction handling.
 *  2) Request-order-preserving result array.
 *  3) Duplicate detection by (cooperative_scope_id, client_request_id, request_type).
 *  4) Offline class A/B/C enforcement.
 */
class SyncBatchProcessor
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly Request $request,
    ) {}

    /**
     * Request types currently supported for class-A replay in this phase.
     *
     * Key: request_type sent by client.
     * Value: resulting entity_type returned in reconciliation response.
     *
     * Example:
     *   request_type=milk_log_create => entity_type=milk_log
     *
     * @var array<string, string>
     */
    private const CLASS_A_REPLAYABLE_TYPES = [
        'milk_log_create' => 'milk_log',
        'field_visit_report_create' => 'field_visit_report',
        'listing_create' => 'marketplace_listing',
        'offer_submit' => 'marketplace_offer',
    ];

    /**
     * Request types explicitly treated as class-B capture-only flows.
     *
     * These are not committed by sync replay and must be confirmed online
     * through dedicated online workflows.
     *
     * @var list<string>
     */
    private const CLASS_B_CAPTURE_ONLY_TYPES = [
        'withdrawal_request_draft',
        'dispute_draft',
        'receipt_confirmation_draft',
    ];

    /**
     * Request types explicitly blocked from offline replay (class C).
     *
     * @var list<string>
     */
    private const CLASS_C_ONLINE_ONLY_TYPES = [
        'approval_decision',
        'maker_checker_decision',
        'marketplace_finalization',
        'gl_posting',
    ];

    /**
     * Server states that are considered protected and must not be overwritten
     * by replayed client payloads.
     *
     * Mapping:
     *  - approved  -> approval_state_locked
     *  - posted    -> ledger_state_locked
     *  - reversed  -> ledger_state_locked
     *  - finalized -> finalization_state_locked
     *
     * @var array<string, string>
     */
    private const PROTECTED_STATE_CONFLICT_CODES = [
        'approved' => 'approval_state_locked',
        'posted' => 'ledger_state_locked',
        'reversed' => 'ledger_state_locked',
        'finalized' => 'finalization_state_locked',
    ];

    /**
     * Conflict codes that can only be actively resolved by privileged users.
     *
     * These are effectively "server truth wins" scenarios where resolution
     * should be governed by higher-trust roles.
     *
     * @var list<string>
     */
    private const PRIVILEGED_ONLY_CONFLICT_CODES = [
        'approval_state_locked',
        'ledger_state_locked',
        'finalization_state_locked',
        'server_state_superseded',
    ];

    /**
     * Process a full sync batch while preserving original item order.
     *
     * @param  list<array<string, mixed>>  $items
     * @return array{processed:int, failed:int, results:list<array<string, mixed>>}
     */
    public function processBatch(User $actor, array $items): array
    {
        $results = [];
        $failed = 0;

        // Iterate in client-provided order so response order mirrors queue order.
        foreach ($items as $item) {
            try {
                // Per-item transaction isolation: one failed item does not rollback others.
                $result = DB::transaction(function () use ($actor, $item): array {
                    return $this->processSingleItem($actor, $item);
                });
            } catch (Throwable $exception) {
                $failed++;

                // On transient or unexpected failure, return failed_sync result.
                // We intentionally do not persist this as authoritative duplicate record
                // so the client can retry safely.
                $result = [
                    'client_request_id' => (string) ($item['client_request_id'] ?? ''),
                    'request_type' => (string) ($item['request_type'] ?? ''),
                    'status' => 'failed_sync',
                    'error_message' => $exception->getMessage(),
                    'processed_at' => now()->toIso8601String(),
                ];
            }

            $results[] = $result;
        }

        return [
            'processed' => count($items),
            'failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * Return sync-status summary for the current actor.
     *
     * @return array<string, mixed>
     */
    public function status(User $actor): array
    {
        $query = SyncReplayItem::query()->where('actor_id', $actor->id);

        $counts = (clone $query)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->toArray();

        $recent = (clone $query)
            ->latest('processed_at')
            ->limit(20)
            ->get()
            ->map(function (SyncReplayItem $item): array {
                return [
                    'client_request_id' => $item->client_request_id,
                    'request_type' => $item->request_type,
                    'status' => $item->status,
                    'entity_type' => $item->entity_type,
                    'entity_id' => $item->entity_id,
                    'conflict_code' => $item->conflict_code,
                    'processed_at' => $item->processed_at?->toIso8601String(),
                ];
            })
            ->all();

        // Complex section summary:
        // Build operator-friendly diagnostics so admin/mobile sync centers can
        // quickly answer three practical questions:
        //  1) What can be retried automatically right now?
        //  2) What needs human resolution and why?
        //  3) What are the dominant failure causes?

        // Retry-eligibility model from offline spec:
        // - retryable_now: failed_sync
        // - requires_user_or_admin_action: conflict, rejected, blocked
        // - terminal_success: synced, duplicate
        $retryEligibility = [
            'retryable_now' => (int) ($counts['failed_sync'] ?? 0),
            'requires_user_or_admin_action' => (int) (
                ($counts['conflict'] ?? 0)
                + ($counts['rejected'] ?? 0)
                + ($counts['blocked'] ?? 0)
            ),
            'terminal_success' => (int) (
                ($counts['synced'] ?? 0)
                + ($counts['duplicate'] ?? 0)
            ),
        ];

        // Conflict breakdown by conflict_code for targeted remediation dashboards.
        // Example output:
        //   {
        //     "approval_state_locked": 3,
        //     "dependency_failed": 2,
        //     "scope_changed": 1
        //   }
        $conflictBreakdown = (clone $query)
            ->where('status', 'conflict')
            ->whereNotNull('conflict_code')
            ->selectRaw('conflict_code, COUNT(*) as aggregate')
            ->groupBy('conflict_code')
            ->pluck('aggregate', 'conflict_code')
            ->map(fn ($count): int => (int) $count)
            ->toArray();

        // Top failure reasons by frequency for quick triage.
        // We include non-conflict terminal failures as well (rejected/blocked/failed_sync).
        $failureReasonSummary = (clone $query)
            ->whereIn('status', ['rejected', 'blocked', 'failed_sync'])
            ->whereNotNull('error_message')
            ->selectRaw('error_message, COUNT(*) as aggregate')
            ->groupBy('error_message')
            ->orderByDesc('aggregate')
            ->limit(5)
            ->get()
            ->map(fn (SyncReplayItem $item): array => [
                'error_message' => (string) $item->error_message,
                'count' => (int) $item->getAttribute('aggregate'),
            ])
            ->all();

        // Resolution queue size: how many persisted rows still require human action.
        $resolutionQueueCount = (int) ((clone $query)
            ->where('resolution_required', true)
            ->count());

        return [
            'counts' => [
                'synced' => (int) ($counts['synced'] ?? 0),
                'duplicate' => (int) ($counts['duplicate'] ?? 0),
                'rejected' => (int) ($counts['rejected'] ?? 0),
                'conflict' => (int) ($counts['conflict'] ?? 0),
                'blocked' => (int) ($counts['blocked'] ?? 0),
                'failed_sync' => (int) ($counts['failed_sync'] ?? 0),
            ],
            'retry_eligibility' => $retryEligibility,
            'resolution_queue_count' => $resolutionQueueCount,
            'conflict_breakdown' => $conflictBreakdown,
            'failure_reason_summary' => $failureReasonSummary,
            'recent_results' => $recent,
        ];
    }

    /**
     * Return unresolved conflict rows available to the actor.
     *
     * Non-privileged actors only see their own rows. Privileged actors can see
     * the cooperative queue to support admin-led remediation.
     *
     * @return array<string, mixed>
     */
    public function conflicts(User $actor, int $perPage = 15): array
    {
        $query = SyncReplayItem::query()
            ->where('status', 'conflict')
            ->where('resolution_required', true)
            ->latest('processed_at');

        if (! $actor->isPrivileged()) {
            $query->where('actor_id', $actor->id);
        } else {
            $query->where('cooperative_scope_id', $this->cooperativeScopeId($actor));
        }

        $paginator = $query->paginate($perPage);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (SyncReplayItem $item): array => $this->serializeConflict($item))
                ->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * Return one conflict row visible to the actor.
     *
     * @return array<string, mixed>
     */
    public function conflictDetail(User $actor, int $conflictId): array
    {
        return $this->serializeConflict($this->findConflictForActor($actor, $conflictId));
    }

    /**
     * Return append-only action history for one conflict row.
     *
     * @return array<string, mixed>
     */
    public function conflictHistory(User $actor, int $conflictId): array
    {
        $conflict = $this->findConflictForActor($actor, $conflictId);

        $history = SyncConflictAction::query()
            ->where('sync_replay_item_id', $conflict->id)
            ->orderBy('id')
            ->get()
            ->map(fn (SyncConflictAction $action): array => $this->serializeConflictAction($action))
            ->all();

        return [
            'conflict_id' => $conflict->id,
            'items' => $history,
        ];
    }

    /**
     * Add a reviewer note to a conflict history stream.
     *
     * @return array<string, mixed>
     */
    public function addConflictNote(User $actor, int $conflictId, string $note): array
    {
        $conflict = $this->findConflictForActor($actor, $conflictId);

        if (! $this->canAddReviewerNotes($actor)) {
            throw new InvalidArgumentException('Only reviewers can add sync conflict notes.');
        }

        $action = $this->recordConflictAction(
            conflict: $conflict,
            actor: $actor,
            actionType: 'note_added',
            note: $note,
            metadata: [
                'resolution_required' => (bool) $conflict->resolution_required,
                'conflict_code' => $conflict->conflict_code,
            ],
        );

        return $this->serializeConflictAction($action);
    }

    /**
     * Resolve a conflict row with one of the supported actions.
     *
     * Supported actions:
     * - view_server_record
     * - create_replacement_submission
     *
     * @param  array<string, mixed>|null  $replacementItem
     * @return array<string, mixed>
     */
    public function resolveConflict(
        User $actor,
        int $conflictId,
        string $action,
        ?string $reason = null,
        ?array $replacementItem = null,
    ): array {
        $normalizedAction = strtolower($action);

        if (! in_array($normalizedAction, ['view_server_record', 'create_replacement_submission'], true)) {
            throw new InvalidArgumentException('Unsupported resolution action provided.');
        }

        return DB::transaction(function () use ($actor, $conflictId, $normalizedAction, $reason, $replacementItem): array {
            $conflict = $this->findConflictForActor($actor, $conflictId);

            if (! in_array($conflict->status, ['conflict'], true) || ! $conflict->resolution_required) {
                throw new InvalidArgumentException('This conflict is already resolved or not eligible for resolution.');
            }

            if ($this->requiresPrivilegedResolver($conflict) && ! $actor->isPrivileged()) {
                throw new InvalidArgumentException('Only privileged roles can resolve this conflict type.');
            }

            if ($actor->isPrivileged() && $this->requiresPrivilegedResolver($conflict) && blank($reason)) {
                throw new InvalidArgumentException('A resolution reason is required for privileged conflict resolution actions.');
            }

            $replacementResult = null;

            if ($normalizedAction === 'create_replacement_submission') {
                if ($replacementItem === null) {
                    throw new InvalidArgumentException('replacement_item is required when creating a replacement submission.');
                }

                // Reuse the same batch processor logic so replacement submissions
                // receive identical validation, class enforcement, duplicate checks,
                // and persistence behavior as normal /sync/batch traffic.
                $replacementBatch = $this->processBatch($actor, [$replacementItem]);
                $replacementResult = $replacementBatch['results'][0] ?? null;
            }

            $conflict->update([
                'resolution_required' => false,
                'resolution_action' => $normalizedAction,
                'resolution_reason' => $reason,
                'resolved_by_user_id' => $actor->id,
                'resolved_at' => now(),
            ]);

            $this->recordConflictAction(
                conflict: $conflict,
                actor: $actor,
                actionType: 'resolved',
                note: $reason,
                metadata: [
                    'resolution_action' => $normalizedAction,
                    'replacement_result_status' => is_array($replacementResult) ? ($replacementResult['status'] ?? null) : null,
                ],
            );

            $this->auditLogger->record(
                action: 'sync.conflict_resolved',
                subject: $conflict,
                context: [
                    'conflict_id' => $conflict->id,
                    'actor_id' => $actor->id,
                    'resolution_action' => $normalizedAction,
                    'resolution_reason' => $reason,
                    'replacement_result_status' => is_array($replacementResult) ? ($replacementResult['status'] ?? null) : null,
                ],
                actor: $actor,
                cooperativeId: $conflict->cooperative_scope_id,
            );

            return [
                'conflict' => $this->serializeConflict($conflict->fresh()),
                'replacement_result' => $replacementResult,
            ];
        });
    }

    /**
     * Process one replay item and return its reconciliation result payload.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function processSingleItem(User $actor, array $item): array
    {
        $clientRequestId = (string) $item['client_request_id'];
        $requestType = (string) $item['request_type'];
        $offlineClass = strtoupper((string) $item['offline_class']);
        $payload = is_array($item['payload']) ? $item['payload'] : [];

        // Normalize payload deterministically before hashing.
        // Example: {b:2,a:1} and {a:1,b:2} must produce the same hash.
        $normalizedPayload = Arr::sortRecursive($payload);
        $payloadHash = hash('sha256', (string) json_encode($normalizedPayload));

        $scopeId = $this->cooperativeScopeId($actor);

        // Validate request_type <-> offline_class compatibility when the request type
        // is known to the server's offline policy catalog.
        //
        // Example mismatch:
        //   request_type=approval_decision (class C) sent with offline_class=A
        //   => rejected (client payload is inconsistent with policy)
        $expectedClass = $this->expectedOfflineClassForRequestType($requestType);

        if ($expectedClass !== null && $offlineClass !== $expectedClass) {
            return $this->persistAndBuildResult(
                actor: $actor,
                scopeId: $scopeId,
                clientRequestId: $clientRequestId,
                requestType: $requestType,
                offlineClass: $offlineClass,
                payloadHash: $payloadHash,
                status: 'rejected',
                errorMessage: 'offline_class does not match request_type policy. Expected '.$expectedClass.'.',
            );
        }

        $existing = SyncReplayItem::query()
            ->where('cooperative_scope_id', $scopeId)
            ->where('client_request_id', $clientRequestId)
            ->where('request_type', $requestType)
            ->first();

        // Duplicate detection flow.
        if ($existing !== null) {
            // Same key + different payload = conflict per spec.
            if ($existing->payload_hash !== $payloadHash) {
                return [
                    'client_request_id' => $clientRequestId,
                    'request_type' => $requestType,
                    'status' => 'conflict',
                    'conflict_code' => 'duplicate_with_payload_mismatch',
                    'server_state_summary' => [
                        'existing_status' => $existing->status,
                        'entity_type' => $existing->entity_type,
                        'entity_id' => $existing->entity_id,
                    ],
                    'resolution_required' => true,
                    'resolution_options' => [
                        'view_server_record',
                        'create_replacement_submission',
                    ],
                    'processed_at' => now()->toIso8601String(),
                ];
            }

            // Same key + same payload + previously synced = duplicate safe retry.
            if ($existing->status === 'synced') {
                return [
                    'client_request_id' => $clientRequestId,
                    'request_type' => $requestType,
                    'status' => 'duplicate',
                    'entity_type' => $existing->entity_type,
                    'entity_id' => $existing->entity_id,
                    'server_state' => $existing->server_state,
                    'processed_at' => now()->toIso8601String(),
                ];
            }

            // Existing non-synced status is returned as authoritative prior result.
            return [
                'client_request_id' => $clientRequestId,
                'request_type' => $requestType,
                'status' => $existing->status,
                'entity_type' => $existing->entity_type,
                'entity_id' => $existing->entity_id,
                'conflict_code' => $existing->conflict_code,
                'server_state_summary' => $existing->server_state_summary,
                'resolution_required' => $existing->resolution_required,
                'resolution_options' => $existing->resolution_options,
                'processed_at' => now()->toIso8601String(),
            ];
        }

        // Complex section summary:
        // Evaluate conflict precedence rules BEFORE applying class-specific replay logic.
        // This guarantees server-authoritative protected states always win against
        // incoming offline edits.
        $precedenceConflict = $this->resolvePrecedenceConflict(
            actor: $actor,
            scopeId: $scopeId,
            clientRequestId: $clientRequestId,
            requestType: $requestType,
            offlineClass: $offlineClass,
            payloadHash: $payloadHash,
            payload: $payload,
        );

        if ($precedenceConflict !== null) {
            return $precedenceConflict;
        }

        // Offline class C is always blocked during replay.
        if ($offlineClass === 'C') {
            return $this->persistAndBuildResult(
                actor: $actor,
                scopeId: $scopeId,
                clientRequestId: $clientRequestId,
                requestType: $requestType,
                offlineClass: $offlineClass,
                payloadHash: $payloadHash,
                status: 'blocked',
                errorMessage: 'Class C action is online-only and cannot be replayed via sync batch.',
            );
        }

        // Offline class B capture-only items are blocked from auto-commit replay.
        if ($offlineClass === 'B') {
            return $this->persistAndBuildResult(
                actor: $actor,
                scopeId: $scopeId,
                clientRequestId: $clientRequestId,
                requestType: $requestType,
                offlineClass: $offlineClass,
                payloadHash: $payloadHash,
                status: 'blocked',
                errorMessage: 'Class B items require explicit online confirmation before commit.',
            );
        }

        // Offline class A request-type guard.
        if ($offlineClass !== 'A') {
            return $this->persistAndBuildResult(
                actor: $actor,
                scopeId: $scopeId,
                clientRequestId: $clientRequestId,
                requestType: $requestType,
                offlineClass: $offlineClass,
                payloadHash: $payloadHash,
                status: 'rejected',
                errorMessage: 'Unknown offline_class. Allowed values are A, B, C.',
            );
        }

        // If request type is unsupported for class A replay, reject.
        if (! array_key_exists($requestType, self::CLASS_A_REPLAYABLE_TYPES)) {
            return $this->persistAndBuildResult(
                actor: $actor,
                scopeId: $scopeId,
                clientRequestId: $clientRequestId,
                requestType: $requestType,
                offlineClass: $offlineClass,
                payloadHash: $payloadHash,
                status: 'rejected',
                errorMessage: 'Unsupported class-A request_type for this phase.',
            );
        }

        // Domain-backed replay for milk logs now writes the actual production row.
        if ($requestType === 'milk_log_create') {
            return $this->processMilkLogCreateReplay(
                actor: $actor,
                scopeId: $scopeId,
                clientRequestId: $clientRequestId,
                requestType: $requestType,
                offlineClass: $offlineClass,
                payloadHash: $payloadHash,
                payload: $payload,
            );
        }

        // Phase-one primitive commit for class-A replayable request types.
        // We mint an entity reference using the replay-row ID to provide deterministic
        // response semantics before domain-specific write handlers are integrated.
        $entityType = self::CLASS_A_REPLAYABLE_TYPES[$requestType];

        $row = SyncReplayItem::query()->create([
            'actor_id' => $actor->id,
            'cooperative_scope_id' => $scopeId,
            'client_request_id' => $clientRequestId,
            'request_type' => $requestType,
            'offline_class' => $offlineClass,
            'payload_hash' => $payloadHash,
            'status' => 'synced',
            'entity_type' => $entityType,
            'entity_id' => null,
            'server_state' => 'synced',
            'processed_at' => now(),
        ]);

        // Example output:
        //   status=synced, entity_type=milk_log, entity_id=<row-id>
        $row->update(['entity_id' => $row->id]);

        return [
            'client_request_id' => $clientRequestId,
            'request_type' => $requestType,
            'status' => 'synced',
            'entity_type' => $entityType,
            'entity_id' => $row->id,
            'server_state' => 'synced',
            'processed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Persist a milk production record from class-A replay payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function processMilkLogCreateReplay(
        User $actor,
        int $scopeId,
        string $clientRequestId,
        string $requestType,
        string $offlineClass,
        string $payloadHash,
        array $payload,
    ): array {
        $memberId = isset($payload['member_id']) ? (int) $payload['member_id'] : 0;
        $quantityLiters = isset($payload['quantity_liters']) ? (float) $payload['quantity_liters'] : 0.0;
        $productionDateRaw = isset($payload['production_date']) ? (string) $payload['production_date'] : '';
        $source = isset($payload['source']) ? (string) $payload['source'] : 'mobile';

        if ($memberId <= 0) {
            return $this->persistAndBuildResult(
                actor: $actor,
                scopeId: $scopeId,
                clientRequestId: $clientRequestId,
                requestType: $requestType,
                offlineClass: $offlineClass,
                payloadHash: $payloadHash,
                status: 'rejected',
                errorMessage: 'milk_log_create requires member_id.',
            );
        }

        if ($quantityLiters <= 0 || $quantityLiters > 1000) {
            return $this->persistAndBuildResult(
                actor: $actor,
                scopeId: $scopeId,
                clientRequestId: $clientRequestId,
                requestType: $requestType,
                offlineClass: $offlineClass,
                payloadHash: $payloadHash,
                status: 'rejected',
                errorMessage: 'milk_log_create quantity_liters must be greater than 0 and at most 1000.',
            );
        }

        try {
            $productionDate = Carbon::parse($productionDateRaw)->toDateString();
        } catch (Throwable) {
            return $this->persistAndBuildResult(
                actor: $actor,
                scopeId: $scopeId,
                clientRequestId: $clientRequestId,
                requestType: $requestType,
                offlineClass: $offlineClass,
                payloadHash: $payloadHash,
                status: 'rejected',
                errorMessage: 'milk_log_create production_date must be a valid date.',
            );
        }

        $member = Member::query()->find($memberId);

        if ($member === null) {
            return $this->persistAndBuildResult(
                actor: $actor,
                scopeId: $scopeId,
                clientRequestId: $clientRequestId,
                requestType: $requestType,
                offlineClass: $offlineClass,
                payloadHash: $payloadHash,
                status: 'rejected',
                errorMessage: 'milk_log_create member_id does not exist.',
            );
        }

        $clusterId = isset($payload['cluster_id']) ? (int) $payload['cluster_id'] : (int) $member->cluster_id;

        if ($clusterId <= 0) {
            return $this->persistAndBuildResult(
                actor: $actor,
                scopeId: $scopeId,
                clientRequestId: $clientRequestId,
                requestType: $requestType,
                offlineClass: $offlineClass,
                payloadHash: $payloadHash,
                status: 'rejected',
                errorMessage: 'milk_log_create requires a valid cluster assignment for the member.',
            );
        }

        if ((int) $member->cluster_id !== $clusterId) {
            return $this->persistAndBuildResult(
                actor: $actor,
                scopeId: $scopeId,
                clientRequestId: $clientRequestId,
                requestType: $requestType,
                offlineClass: $offlineClass,
                payloadHash: $payloadHash,
                status: 'rejected',
                errorMessage: 'milk_log_create cluster_id must match the member assignment.',
            );
        }

        $milkLog = MilkProductionLog::query()->create([
            'cooperative_id' => $member->cooperative_id,
            'cluster_id' => $clusterId,
            'member_id' => $member->id,
            'recorded_by_user_id' => $actor->id,
            'quantity_liters' => $quantityLiters,
            'production_date' => $productionDate,
            'source' => $source,
            'status' => 'recorded',
        ]);

        $row = SyncReplayItem::query()->create([
            'actor_id' => $actor->id,
            'cooperative_scope_id' => $scopeId,
            'client_request_id' => $clientRequestId,
            'request_type' => $requestType,
            'offline_class' => $offlineClass,
            'payload_hash' => $payloadHash,
            'status' => 'synced',
            'entity_type' => 'milk_log',
            'entity_id' => $milkLog->id,
            'server_state' => 'recorded',
            'processed_at' => now(),
        ]);

        $this->auditLogger->record(
            action: 'milk_production.recorded',
            subject: $milkLog,
            actor: $actor,
            cooperativeId: (int) $member->cooperative_id,
            context: [
                'member_id' => $member->id,
                'cluster_id' => $clusterId,
                'quantity_liters' => $quantityLiters,
                'production_date' => $productionDate,
                'source' => $source,
                'sync_client_request_id' => $clientRequestId,
                'sync_replay_item_id' => $row->id,
            ],
        );

        return [
            'client_request_id' => $clientRequestId,
            'request_type' => $requestType,
            'status' => 'synced',
            'entity_type' => 'milk_log',
            'entity_id' => $milkLog->id,
            'server_state' => 'recorded',
            'processed_at' => $row->processed_at?->toIso8601String(),
        ];
    }

    /**
     * Persist a non-synced outcome and return a response payload.
     */
    private function persistAndBuildResult(
        User $actor,
        int $scopeId,
        string $clientRequestId,
        string $requestType,
        string $offlineClass,
        string $payloadHash,
        string $status,
        string $errorMessage,
        ?string $conflictCode = null,
        ?array $serverStateSummary = null,
        bool $resolutionRequired = false,
        ?array $resolutionOptions = null,
    ): array {
        $row = SyncReplayItem::query()->create([
            'actor_id' => $actor->id,
            'cooperative_scope_id' => $scopeId,
            'client_request_id' => $clientRequestId,
            'request_type' => $requestType,
            'offline_class' => $offlineClass,
            'payload_hash' => $payloadHash,
            'status' => $status,
            'error_message' => $errorMessage,
            'conflict_code' => $conflictCode,
            'server_state_summary' => $serverStateSummary,
            'resolution_required' => $resolutionRequired,
            'resolution_options' => $resolutionOptions,
            'processed_at' => now(),
        ]);

        return [
            'client_request_id' => $clientRequestId,
            'request_type' => $requestType,
            'status' => $status,
            'error_message' => $errorMessage,
            'conflict_code' => $conflictCode,
            'server_state_summary' => $serverStateSummary,
            'resolution_required' => $resolutionRequired,
            'resolution_options' => $resolutionOptions,
            'processed_at' => $row->processed_at?->toIso8601String(),
        ];
    }

    /**
     * Evaluate precedence conflict rules from the offline reconciliation spec.
     *
     * Returns null when no precedence conflict is present.
     * Returns a fully built conflict result (and persists it) when a rule triggers.
     *
     * Conflict triggers implemented:
     *  1) Protected server status lock (approved/posted/reversed/finalized).
     *  2) Explicit scope_changed flag from server-side prevalidation.
     *  3) Dependency failure when a prerequisite replay item is missing or not synced/duplicate.
     *  4) Explicit server_state_superseded and admin_override flags.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function resolvePrecedenceConflict(
        User $actor,
        int $scopeId,
        string $clientRequestId,
        string $requestType,
        string $offlineClass,
        string $payloadHash,
        array $payload,
    ): ?array {
        // 1) Protected state precedence lock.
        $serverStatus = strtolower((string) ($payload['server_status'] ?? ''));

        if ($serverStatus !== '' && array_key_exists($serverStatus, self::PROTECTED_STATE_CONFLICT_CODES)) {
            return $this->persistAndBuildResult(
                actor: $actor,
                scopeId: $scopeId,
                clientRequestId: $clientRequestId,
                requestType: $requestType,
                offlineClass: $offlineClass,
                payloadHash: $payloadHash,
                status: 'conflict',
                errorMessage: 'Server state is protected and cannot be overwritten by replay.',
                conflictCode: self::PROTECTED_STATE_CONFLICT_CODES[$serverStatus],
                serverStateSummary: [
                    'status' => $serverStatus,
                ],
                resolutionRequired: true,
                resolutionOptions: [
                    'view_server_record',
                    'create_replacement_submission',
                ],
            );
        }

        // 2) Scope change conflict.
        if (($payload['scope_changed'] ?? false) === true) {
            return $this->persistAndBuildResult(
                actor: $actor,
                scopeId: $scopeId,
                clientRequestId: $clientRequestId,
                requestType: $requestType,
                offlineClass: $offlineClass,
                payloadHash: $payloadHash,
                status: 'conflict',
                errorMessage: 'Replay item scope no longer matches current server scope.',
                conflictCode: 'scope_changed',
                serverStateSummary: [
                    'reason' => 'scope_changed',
                ],
                resolutionRequired: true,
                resolutionOptions: [
                    'view_server_record',
                    'create_replacement_submission',
                ],
            );
        }

        // 3) Dependency failure conflict.
        $dependsOnClientRequestId = (string) ($payload['depends_on_client_request_id'] ?? '');
        $dependsOnRequestType = (string) ($payload['depends_on_request_type'] ?? '');

        if ($dependsOnClientRequestId !== '' && $dependsOnRequestType !== '') {
            $dependencyRow = SyncReplayItem::query()
                ->where('cooperative_scope_id', $scopeId)
                ->where('client_request_id', $dependsOnClientRequestId)
                ->where('request_type', $dependsOnRequestType)
                ->first();

            // Dependency must exist and have successful replay outcome.
            // Valid outcomes considered satisfied in phase one: synced or duplicate.
            if ($dependencyRow === null || ! in_array($dependencyRow->status, ['synced', 'duplicate'], true)) {
                return $this->persistAndBuildResult(
                    actor: $actor,
                    scopeId: $scopeId,
                    clientRequestId: $clientRequestId,
                    requestType: $requestType,
                    offlineClass: $offlineClass,
                    payloadHash: $payloadHash,
                    status: 'conflict',
                    errorMessage: 'Prerequisite replay item failed, was blocked, or is missing.',
                    conflictCode: 'dependency_failed',
                    serverStateSummary: [
                        'depends_on_client_request_id' => $dependsOnClientRequestId,
                        'depends_on_request_type' => $dependsOnRequestType,
                        'dependency_status' => $dependencyRow?->status,
                    ],
                    resolutionRequired: true,
                    resolutionOptions: [
                        'view_server_record',
                        'create_replacement_submission',
                    ],
                );
            }
        }

        // 4) Explicit superseded/admin override conflict flags.
        if (($payload['server_state_superseded'] ?? false) === true || ($payload['admin_override'] ?? false) === true) {
            return $this->persistAndBuildResult(
                actor: $actor,
                scopeId: $scopeId,
                clientRequestId: $clientRequestId,
                requestType: $requestType,
                offlineClass: $offlineClass,
                payloadHash: $payloadHash,
                status: 'conflict',
                errorMessage: 'Server state superseded this replay item.',
                conflictCode: 'server_state_superseded',
                serverStateSummary: [
                    'server_state_superseded' => true,
                    'admin_override' => (bool) ($payload['admin_override'] ?? false),
                ],
                resolutionRequired: true,
                resolutionOptions: [
                    'view_server_record',
                    'create_replacement_submission',
                ],
            );
        }

        return null;
    }

    /**
     * Resolve actor cooperative scope used by duplicate detection boundaries.
     *
     * If the actor has no cooperative scope assignment, use 0 as a sentinel.
     */
    private function cooperativeScopeId(User $actor): int
    {
        return (int) ($actor->scopes()
            ->where('scope_type', 'cooperative')
            ->value('scope_id') ?? 0);
    }

    /**
     * Resolve the policy-defined offline class for a request type.
     *
     * Returns null when request type is unknown to this phase.
     */
    private function expectedOfflineClassForRequestType(string $requestType): ?string
    {
        if (array_key_exists($requestType, self::CLASS_A_REPLAYABLE_TYPES)) {
            return 'A';
        }

        if (in_array($requestType, self::CLASS_B_CAPTURE_ONLY_TYPES, true)) {
            return 'B';
        }

        if (in_array($requestType, self::CLASS_C_ONLINE_ONLY_TYPES, true)) {
            return 'C';
        }

        return null;
    }

    /**
     * Find one conflict row while enforcing role-aware visibility rules.
     */
    private function findConflictForActor(User $actor, int $conflictId): SyncReplayItem
    {
        $query = SyncReplayItem::query()->whereKey($conflictId);

        if (! $actor->isPrivileged()) {
            $query->where('actor_id', $actor->id);
        } else {
            $query->where('cooperative_scope_id', $this->cooperativeScopeId($actor));
        }

        return $query->firstOrFail();
    }

    /**
     * Convert a SyncReplayItem model into API-friendly conflict JSON.
     *
     * @return array<string, mixed>
     */
    private function serializeConflict(SyncReplayItem $item): array
    {
        return [
            'id' => $item->id,
            'actor_id' => $item->actor_id,
            'cooperative_scope_id' => $item->cooperative_scope_id,
            'client_request_id' => $item->client_request_id,
            'request_type' => $item->request_type,
            'status' => $item->status,
            'conflict_code' => $item->conflict_code,
            'error_message' => $item->error_message,
            'server_state_summary' => $item->server_state_summary,
            'resolution_required' => $item->resolution_required,
            'resolution_options' => $item->resolution_options,
            'resolution_action' => $item->resolution_action,
            'resolution_reason' => $item->resolution_reason,
            'resolved_by_user_id' => $item->resolved_by_user_id,
            'resolved_at' => $item->resolved_at?->toIso8601String(),
            'processed_at' => $item->processed_at?->toIso8601String(),
        ];
    }

    /**
     * Determine if a conflict type requires privileged-role resolver authority.
     */
    private function requiresPrivilegedResolver(SyncReplayItem $item): bool
    {
        return in_array((string) $item->conflict_code, self::PRIVILEGED_ONLY_CONFLICT_CODES, true);
    }

    /**
     * Persist a single append-only action row for conflict lifecycle history.
     *
     * @param  array<string, mixed>  $metadata
     */
    private function recordConflictAction(
        SyncReplayItem $conflict,
        ?User $actor,
        string $actionType,
        ?string $note = null,
        array $metadata = [],
    ): SyncConflictAction {
        return SyncConflictAction::query()->create([
            'sync_replay_item_id' => $conflict->id,
            'actor_id' => $actor?->id,
            'actor_role' => $actor?->role?->value,
            'action_type' => $actionType,
            'note' => $note,
            'metadata' => $metadata !== [] ? $metadata : null,
            'correlation_id' => $this->request->attributes->get('correlation_id'),
        ]);
    }

    /**
     * Convert one action model to API payload.
     *
     * @return array<string, mixed>
     */
    private function serializeConflictAction(SyncConflictAction $action): array
    {
        return [
            'id' => $action->id,
            'sync_replay_item_id' => $action->sync_replay_item_id,
            'actor_id' => $action->actor_id,
            'actor_role' => $action->actor_role,
            'action_type' => $action->action_type,
            'note' => $action->note,
            'metadata' => $action->metadata,
            'correlation_id' => $action->correlation_id,
            'created_at' => $action->created_at?->toIso8601String(),
        ];
    }

    /**
     * Determine if actor is allowed to add reviewer notes.
     */
    private function canAddReviewerNotes(User $actor): bool
    {
        return in_array($actor->role->value, [
            'system_admin',
            'country_admin',
            'coop_admin',
            'cluster_supervisor',
            'finance_officer',
            'treasurer',
            'marketplace_manager',
        ], true);
    }
}
