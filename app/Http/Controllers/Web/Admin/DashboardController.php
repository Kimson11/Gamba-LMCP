<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cluster;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\MemberAssignment;
use App\Models\MilkProductionLog;
use App\Models\SyncReplayItem;
use App\Support\ScopeAccess;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ScopeAccess $scopeAccess,
    ) {}

    public function __invoke(): View
    {
        $actor = request()->user();

        abort_unless($actor !== null, 401);

        $memberQuery = Member::query();
        $clusterQuery = Cluster::query();
        $cooperativeQuery = Cooperative::query();
        $assignmentQuery = MemberAssignment::query()->where('approval_status', 'pending_approval');
        $milkQuery = MilkProductionLog::query();
        $syncConflictQuery = SyncReplayItem::query()
            ->where('status', 'conflict')
            ->where('resolution_required', true);

        $this->scopeAccess->applyCooperativeScope($actor, $memberQuery, 'cooperative_id');
        $this->scopeAccess->applyCooperativeScope($actor, $clusterQuery, 'cooperative_id');
        $this->scopeAccess->applyCooperativeScope($actor, $cooperativeQuery, 'id');
        $this->scopeAccess->applyCooperativeScope($actor, $assignmentQuery, 'cooperative_id');
        $this->scopeAccess->applyCooperativeScope($actor, $milkQuery, 'cooperative_id');
        $this->scopeAccess->applyCooperativeScope($actor, $syncConflictQuery, 'cooperative_scope_id');

        $todayMilkLiters = (float) (clone $milkQuery)
            ->whereDate('production_date', now()->toDateString())
            ->sum('quantity_liters');

        $sevenDayMilkLiters = (float) (clone $milkQuery)
            ->whereDate('production_date', '>=', now()->subDays(6)->toDateString())
            ->sum('quantity_liters');

        $pendingAssignments = (int) (clone $assignmentQuery)->count();
        $unresolvedConflicts = (int) (clone $syncConflictQuery)->count();

        $stats = [
            [
                'label' => 'Active cooperatives',
                'value' => (int) (clone $cooperativeQuery)->where('status', 'active')->count(),
                'accent_style' => 'background-image: linear-gradient(90deg, rgba(252, 211, 77, 0.85), rgba(253, 230, 138, 0.7), rgba(255, 237, 213, 0.95));',
            ],
            [
                'label' => 'Active clusters',
                'value' => (int) (clone $clusterQuery)->where('status', 'active')->count(),
                'accent_style' => 'background-image: linear-gradient(90deg, rgba(103, 232, 249, 0.85), rgba(153, 246, 228, 0.7), rgba(236, 254, 255, 0.95));',
            ],
            [
                'label' => 'Active members',
                'value' => (int) (clone $memberQuery)->where('status', 'active')->count(),
                'accent_style' => 'background-image: linear-gradient(90deg, rgba(110, 231, 183, 0.85), rgba(190, 242, 100, 0.7), rgba(236, 253, 245, 0.95));',
            ],
            [
                'label' => 'Today milk volume',
                'value' => number_format($todayMilkLiters, 1).' L',
                'accent_style' => 'background-image: linear-gradient(90deg, rgba(125, 211, 252, 0.85), rgba(165, 243, 252, 0.7), rgba(240, 249, 255, 0.95));',
            ],
            [
                'label' => '7 day milk volume',
                'value' => number_format($sevenDayMilkLiters, 1).' L',
                'accent_style' => 'background-image: linear-gradient(90deg, rgba(196, 181, 253, 0.85), rgba(245, 208, 254, 0.7), rgba(245, 243, 255, 0.95));',
            ],
            [
                'label' => 'Pending approvals',
                'value' => $pendingAssignments,
                'accent_style' => 'background-image: linear-gradient(90deg, rgba(253, 164, 175, 0.85), rgba(254, 215, 170, 0.7), rgba(255, 241, 242, 0.95));',
            ],
        ];

        $exceptions = [
            [
                'label' => 'Pending member assignments',
                'count' => $pendingAssignments,
                'href' => route('admin.assignments.index'),
            ],
            [
                'label' => 'Unresolved sync conflicts',
                'count' => $unresolvedConflicts,
                'href' => route('admin.notifications.index'),
            ],
        ];

        return view('admin.dashboard', [
            'scopeSummary' => $this->scopeAccess->summary($actor),
            'stats' => $stats,
            'exceptions' => $exceptions,
            'unresolvedConflicts' => $unresolvedConflicts,
        ]);
    }
}
