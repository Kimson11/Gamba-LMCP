<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\RejectMemberAssignmentRequest;
use App\Models\Member;
use App\Models\MemberAssignment;
use App\Services\ApprovalEventStream;
use App\Services\AuditLogger;
use App\Support\ScopeAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class MemberAssignmentController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly ApprovalEventStream $approvalEventStream,
        private readonly ScopeAccess $scopeAccess,
    ) {}

    public function index(): View
    {
        $actor = request()->user();

        abort_unless($actor !== null, 401);

        $query = MemberAssignment::query()
            ->with(['member', 'fromCluster', 'toCluster'])
            ->where('approval_status', 'pending_approval')
            ->latest('id');

        $this->scopeAccess->applyCooperativeScope($actor, $query, 'cooperative_id');

        $assignments = $query->paginate(20);

        return view('admin.assignments.index', [
            'assignments' => $assignments,
        ]);
    }

    public function approve(MemberAssignment $memberAssignment): RedirectResponse
    {
        $actor = request()->user();

        abort_unless($actor !== null, 401);

        if (! $this->scopeAccess->canAccessCooperative($actor, (int) $memberAssignment->cooperative_id)) {
            abort(403);
        }

        if ($memberAssignment->approval_status !== 'pending_approval') {
            return back()->withErrors([
                'approval' => 'Only pending assignments can be approved.',
            ]);
        }

        if ((int) $memberAssignment->assigned_by_user_id === (int) $actor->id) {
            return back()->withErrors([
                'approval' => 'Assignment requester cannot approve their own request.',
            ]);
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
                'approved_by_user_id' => $actor->id,
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
                    'reviewed_by_user_id' => $actor->id,
                    'decision_latency_seconds' => $decisionLatencySeconds,
                    'reviewed_at' => $reviewedAt->toIso8601String(),
                ],
            );
        });

        return back()->with('status', 'Member assignment approved.');
    }

    public function reject(RejectMemberAssignmentRequest $request, MemberAssignment $memberAssignment): RedirectResponse
    {
        $actor = $request->user();

        abort_unless($actor !== null, 401);

        if (! $this->scopeAccess->canAccessCooperative($actor, (int) $memberAssignment->cooperative_id)) {
            abort(403);
        }

        if ($memberAssignment->approval_status !== 'pending_approval') {
            return back()->withErrors([
                'approval' => 'Only pending assignments can be rejected.',
            ]);
        }

        if ((int) $memberAssignment->assigned_by_user_id === (int) $actor->id) {
            return back()->withErrors([
                'approval' => 'Assignment requester cannot reject their own request.',
            ]);
        }

        DB::transaction(function () use ($memberAssignment, $actor, $request): void {
            $reviewedAt = now();
            $decisionLatencySeconds = (int) ($memberAssignment->created_at?->diffInSeconds($reviewedAt) ?? 0);
            $reason = (string) $request->validated('reason');

            $memberAssignment->update([
                'approval_status' => 'rejected',
                'rejected_by_user_id' => $actor->id,
                'rejected_at' => $reviewedAt,
                'rejection_reason' => $reason,
            ]);

            $this->approvalEventStream->reject(
                entityType: 'member_assignment',
                entityId: $memberAssignment->id,
                reason: $reason,
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
                    'reviewed_by_user_id' => $actor->id,
                    'decision_latency_seconds' => $decisionLatencySeconds,
                    'reviewed_at' => $reviewedAt->toIso8601String(),
                    'reason' => $reason,
                ],
            );
        });

        return back()->with('status', 'Member assignment rejected.');
    }
}
