<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AssignMemberClusterRequest;
use App\Http\Requests\Api\CreateMemberRequest;
use App\Models\Cluster;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\MemberAssignment;
use App\Services\ApprovalEventStream;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use App\Support\ScopeAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MemberController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly ApprovalEventStream $approvalEventStream,
        private readonly ScopeAccess $scopeAccess,
    ) {}

    /**
     * Return members assigned to a cluster.
     */
    public function indexByCluster(Request $request, Cluster $cluster): JsonResponse
    {
        if (! $this->scopeAccess->canAccessCluster($request->user(), $cluster)) {
            return $this->forbiddenScopeResponse();
        }

        $members = Member::query()
            ->where('cluster_id', $cluster->id)
            ->orderBy('member_number')
            ->get()
            ->map(fn (Member $member): array => [
                'id' => $member->id,
                'cooperative_id' => $member->cooperative_id,
                'cluster_id' => $member->cluster_id,
                'user_id' => $member->user_id,
                'member_number' => $member->member_number,
                'first_name' => $member->first_name,
                'last_name' => $member->last_name,
                'phone' => $member->phone,
                'status' => $member->status,
                'created_at' => $member->created_at?->toIso8601String(),
            ])
            ->all();

        return ApiResponse::success([
            'cluster_id' => $cluster->id,
            'items' => $members,
        ]);
    }

    /**
     * Create one member profile.
     */
    public function store(CreateMemberRequest $request): JsonResponse
    {
        if (! $this->scopeAccess->canAccessCooperative($request->user(), (int) $request->validated('cooperative_id'))) {
            return $this->forbiddenScopeResponse();
        }

        $member = Member::query()->create($request->validated());

        return ApiResponse::success([
            'id' => $member->id,
            'cooperative_id' => $member->cooperative_id,
            'cluster_id' => $member->cluster_id,
            'user_id' => $member->user_id,
            'member_number' => $member->member_number,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'phone' => $member->phone,
            'status' => $member->status,
            'created_at' => $member->created_at?->toIso8601String(),
        ], 201);
    }

    /**
     * Return assignment timeline for one member.
     */
    public function assignments(Request $request, Member $member): JsonResponse
    {
        if (! $this->scopeAccess->canAccessMember($request->user(), $member)) {
            return $this->forbiddenScopeResponse();
        }

        $items = MemberAssignment::query()
            ->where('member_id', $member->id)
            ->orderBy('id')
            ->get()
            ->map(fn (MemberAssignment $assignment): array => [
                'id' => $assignment->id,
                'member_id' => $assignment->member_id,
                'cooperative_id' => $assignment->cooperative_id,
                'from_cluster_id' => $assignment->from_cluster_id,
                'to_cluster_id' => $assignment->to_cluster_id,
                'assigned_by_user_id' => $assignment->assigned_by_user_id,
                'reason' => $assignment->reason,
                'approval_status' => $assignment->approval_status,
                'approved_by_user_id' => $assignment->approved_by_user_id,
                'approved_at' => $assignment->approved_at?->toIso8601String(),
                'rejected_by_user_id' => $assignment->rejected_by_user_id,
                'rejected_at' => $assignment->rejected_at?->toIso8601String(),
                'rejection_reason' => $assignment->rejection_reason,
                'correlation_id' => $assignment->correlation_id,
                'created_at' => $assignment->created_at?->toIso8601String(),
            ])
            ->all();

        return ApiResponse::success([
            'member_id' => $member->id,
            'items' => $items,
        ]);
    }

    /**
     * Return filtered assignment history with scope-aware visibility.
     */
    public function assignmentHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cooperative_id' => ['nullable', 'integer', 'exists:cooperatives,id'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'approval_status' => ['nullable', 'string', 'in:pending_approval,approved,rejected'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $actor = $request->user();
        $query = MemberAssignment::query()->latest('id');

        $this->scopeAccess->applyCooperativeScope($actor, $query, 'cooperative_id');

        if (isset($validated['cooperative_id'])) {
            $query->where('cooperative_id', (int) $validated['cooperative_id']);
        }

        if (isset($validated['member_id'])) {
            $query->where('member_id', (int) $validated['member_id']);
        }

        if (isset($validated['approval_status'])) {
            $query->where('approval_status', (string) $validated['approval_status']);
        }

        if (isset($validated['country_code'])) {
            $countryCode = strtoupper((string) $validated['country_code']);

            $cooperativeIds = Cooperative::query()
                ->where('country_code', $countryCode)
                ->pluck('id')
                ->all();

            $query->whereIn('cooperative_id', $cooperativeIds);
        }

        $paginator = $query->paginate((int) ($validated['per_page'] ?? 20));

        return ApiResponse::success([
            'items' => $paginator->getCollection()->map(fn (MemberAssignment $assignment): array => [
                'id' => $assignment->id,
                'member_id' => $assignment->member_id,
                'cooperative_id' => $assignment->cooperative_id,
                'from_cluster_id' => $assignment->from_cluster_id,
                'to_cluster_id' => $assignment->to_cluster_id,
                'assigned_by_user_id' => $assignment->assigned_by_user_id,
                'reason' => $assignment->reason,
                'approval_status' => $assignment->approval_status,
                'approved_by_user_id' => $assignment->approved_by_user_id,
                'approved_at' => $assignment->approved_at?->toIso8601String(),
                'rejected_by_user_id' => $assignment->rejected_by_user_id,
                'rejected_at' => $assignment->rejected_at?->toIso8601String(),
                'rejection_reason' => $assignment->rejection_reason,
                'created_at' => $assignment->created_at?->toIso8601String(),
            ])->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Return pending assignment queue for approval reviewers.
     */
    public function pendingAssignments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cooperative_id' => ['nullable', 'integer', 'exists:cooperatives,id'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'assigned_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'to_cluster_id' => ['nullable', 'integer', 'exists:clusters,id'],
            'older_than_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $actor = $request->user();
        $query = MemberAssignment::query()
            ->where('approval_status', 'pending_approval')
            ->latest('id');

        $this->scopeAccess->applyCooperativeScope($actor, $query, 'cooperative_id');

        if (isset($validated['cooperative_id'])) {
            $query->where('cooperative_id', (int) $validated['cooperative_id']);
        }

        if (isset($validated['member_id'])) {
            $query->where('member_id', (int) $validated['member_id']);
        }

        if (isset($validated['assigned_by_user_id'])) {
            $query->where('assigned_by_user_id', (int) $validated['assigned_by_user_id']);
        }

        if (isset($validated['to_cluster_id'])) {
            $query->where('to_cluster_id', (int) $validated['to_cluster_id']);
        }

        if (isset($validated['older_than_hours'])) {
            $query->where('created_at', '<=', now()->subHours((int) $validated['older_than_hours']));
        }

        if (isset($validated['country_code'])) {
            $countryCode = strtoupper((string) $validated['country_code']);

            $cooperativeIds = Cooperative::query()
                ->where('country_code', $countryCode)
                ->pluck('id')
                ->all();

            $query->whereIn('cooperative_id', $cooperativeIds);
        }

        $paginator = $query->paginate((int) ($validated['per_page'] ?? 20));

        return ApiResponse::success([
            'items' => $paginator->getCollection()->map(fn (MemberAssignment $assignment): array => [
                'id' => $assignment->id,
                'member_id' => $assignment->member_id,
                'cooperative_id' => $assignment->cooperative_id,
                'from_cluster_id' => $assignment->from_cluster_id,
                'to_cluster_id' => $assignment->to_cluster_id,
                'assigned_by_user_id' => $assignment->assigned_by_user_id,
                'reason' => $assignment->reason,
                'approval_status' => $assignment->approval_status,
                'created_at' => $assignment->created_at?->toIso8601String(),
                'pending_for_seconds' => $assignment->created_at?->diffInSeconds(now()),
            ])->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Reassign a member to a cluster in the same cooperative.
     */
    public function assignCluster(AssignMemberClusterRequest $request, Member $member): JsonResponse
    {
        $validated = $request->validated();
        $targetCluster = Cluster::query()->findOrFail((int) $validated['cluster_id']);
        $actor = $request->user();

        if (! $this->scopeAccess->canAccessMember($actor, $member) || ! $this->scopeAccess->canAccessCluster($actor, $targetCluster)) {
            return $this->forbiddenScopeResponse();
        }

        if ((int) $member->cluster_id === (int) $targetCluster->id) {
            return ApiResponse::error(
                message: 'Member is already assigned to the selected cluster.',
                code: 'validation_failed',
                status: 422,
                errors: [
                    'cluster_id' => ['Member is already assigned to the selected cluster.'],
                ],
            );
        }

        $assignment = DB::transaction(function () use ($member, $targetCluster, $validated, $request, $actor): MemberAssignment {
            $fromClusterId = $member->cluster_id;

            $assignment = MemberAssignment::query()->create([
                'member_id' => $member->id,
                'cooperative_id' => $member->cooperative_id,
                'from_cluster_id' => $fromClusterId,
                'to_cluster_id' => $targetCluster->id,
                'assigned_by_user_id' => $actor?->id,
                'reason' => $validated['reason'] ?? null,
                'approval_status' => 'pending_approval',
                'correlation_id' => $request->attributes->get('correlation_id'),
            ]);

            $this->approvalEventStream->submit(
                entityType: 'member_assignment',
                entityId: $assignment->id,
                metadata: [
                    'member_id' => $member->id,
                    'from_cluster_id' => $fromClusterId,
                    'to_cluster_id' => $targetCluster->id,
                ],
                actor: $actor,
            );

            $this->auditLogger->record(
                action: 'member_assignment.submitted',
                subject: $member,
                actor: $actor,
                cooperativeId: (int) $member->cooperative_id,
                context: [
                    'member_id' => $member->id,
                    'from_cluster_id' => $fromClusterId,
                    'to_cluster_id' => $targetCluster->id,
                    'reason' => $validated['reason'] ?? null,
                    'assignment_id' => $assignment->id,
                ],
            );

            return $assignment;
        });

        return ApiResponse::success([
            'id' => $assignment->id,
            'member_id' => $assignment->member_id,
            'cooperative_id' => $assignment->cooperative_id,
            'from_cluster_id' => $assignment->from_cluster_id,
            'to_cluster_id' => $assignment->to_cluster_id,
            'assigned_by_user_id' => $assignment->assigned_by_user_id,
            'reason' => $assignment->reason,
            'approval_status' => $assignment->approval_status,
            'correlation_id' => $assignment->correlation_id,
            'created_at' => $assignment->created_at?->toIso8601String(),
        ], 201);
    }

    /**
     * Approve a pending member assignment and apply it to member cluster.
     */
    public function approveAssignment(Request $request, MemberAssignment $memberAssignment): JsonResponse
    {
        $actor = $request->user();

        if (! $this->scopeAccess->canAccessCooperative($actor, (int) $memberAssignment->cooperative_id)) {
            return $this->forbiddenScopeResponse();
        }

        if ($memberAssignment->approval_status !== 'pending_approval') {
            return ApiResponse::error(
                message: 'Only pending assignments can be approved.',
                code: 'validation_failed',
                status: 422,
            );
        }

        if ($actor !== null && (int) $memberAssignment->assigned_by_user_id === (int) $actor->id) {
            return ApiResponse::error(
                message: 'Assignment requester cannot approve their own request.',
                code: 'permission_denied',
                status: 403,
            );
        }

        DB::transaction(function () use ($memberAssignment, $actor): void {
            $member = Member::query()->findOrFail($memberAssignment->member_id);
            $reviewedAt = now();
            $decisionLatencySeconds = (int) ($memberAssignment->created_at?->diffInSeconds($reviewedAt) ?? 0);

            $member->update([
                'cluster_id' => $memberAssignment->to_cluster_id,
            ]);

            $memberAssignment->update([
                'approval_status' => 'approved',
                'approved_by_user_id' => $actor?->id,
                'approved_at' => $reviewedAt,
                'rejected_by_user_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            $this->approvalEventStream->approve(
                entityType: 'member_assignment',
                entityId: $memberAssignment->id,
                metadata: [
                    'member_id' => $member->id,
                    'to_cluster_id' => $memberAssignment->to_cluster_id,
                ],
                actor: $actor,
            );

            $this->auditLogger->record(
                action: 'member_assignment.approved',
                subject: $member,
                actor: $actor,
                cooperativeId: (int) $memberAssignment->cooperative_id,
                context: [
                    'assignment_id' => $memberAssignment->id,
                    'member_id' => $member->id,
                    'to_cluster_id' => $memberAssignment->to_cluster_id,
                    'from_cluster_id' => $memberAssignment->from_cluster_id,
                    'submitted_by_user_id' => $memberAssignment->assigned_by_user_id,
                    'reviewed_by_user_id' => $actor?->id,
                    'decision_latency_seconds' => $decisionLatencySeconds,
                    'reviewed_at' => $reviewedAt->toIso8601String(),
                ],
            );
        });

        $assignment = $memberAssignment->fresh();

        return ApiResponse::success([
            'id' => $assignment->id,
            'approval_status' => $assignment->approval_status,
            'approved_by_user_id' => $assignment->approved_by_user_id,
            'approved_at' => $assignment->approved_at?->toIso8601String(),
        ]);
    }

    /**
     * Reject a pending member assignment with reason.
     */
    public function rejectAssignment(Request $request, MemberAssignment $memberAssignment): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $actor = $request->user();

        if (! $this->scopeAccess->canAccessCooperative($actor, (int) $memberAssignment->cooperative_id)) {
            return $this->forbiddenScopeResponse();
        }

        if ($memberAssignment->approval_status !== 'pending_approval') {
            return ApiResponse::error(
                message: 'Only pending assignments can be rejected.',
                code: 'validation_failed',
                status: 422,
            );
        }

        if ($actor !== null && (int) $memberAssignment->assigned_by_user_id === (int) $actor->id) {
            return ApiResponse::error(
                message: 'Assignment requester cannot reject their own request.',
                code: 'permission_denied',
                status: 403,
            );
        }

        try {
            DB::transaction(function () use ($memberAssignment, $validated, $actor): void {
                $reviewedAt = now();
                $decisionLatencySeconds = (int) ($memberAssignment->created_at?->diffInSeconds($reviewedAt) ?? 0);

                $memberAssignment->update([
                    'approval_status' => 'rejected',
                    'rejected_by_user_id' => $actor?->id,
                    'rejected_at' => $reviewedAt,
                    'rejection_reason' => $validated['reason'],
                ]);

                $this->approvalEventStream->reject(
                    entityType: 'member_assignment',
                    entityId: $memberAssignment->id,
                    reason: $validated['reason'],
                    actor: $actor,
                );

                $this->auditLogger->record(
                    action: 'member_assignment.rejected',
                    subject: $memberAssignment,
                    actor: $actor,
                    cooperativeId: (int) $memberAssignment->cooperative_id,
                    context: [
                        'assignment_id' => $memberAssignment->id,
                        'member_id' => $memberAssignment->member_id,
                        'from_cluster_id' => $memberAssignment->from_cluster_id,
                        'to_cluster_id' => $memberAssignment->to_cluster_id,
                        'submitted_by_user_id' => $memberAssignment->assigned_by_user_id,
                        'reviewed_by_user_id' => $actor?->id,
                        'decision_latency_seconds' => $decisionLatencySeconds,
                        'reviewed_at' => $reviewedAt->toIso8601String(),
                        'reason' => $validated['reason'],
                    ],
                );
            });
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error(
                message: $exception->getMessage(),
                code: 'validation_failed',
                status: 422,
            );
        }

        $assignment = $memberAssignment->fresh();

        return ApiResponse::success([
            'id' => $assignment->id,
            'approval_status' => $assignment->approval_status,
            'rejected_by_user_id' => $assignment->rejected_by_user_id,
            'rejected_at' => $assignment->rejected_at?->toIso8601String(),
            'rejection_reason' => $assignment->rejection_reason,
        ]);
    }

    private function forbiddenScopeResponse(): JsonResponse
    {
        return ApiResponse::error(
            message: 'You are not authorized to access this resource within your assigned scope.',
            code: 'permission_denied',
            status: 403,
        );
    }
}
