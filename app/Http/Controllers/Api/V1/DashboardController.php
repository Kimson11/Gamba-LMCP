<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cluster;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\MemberAssignment;
use App\Models\MilkProductionLog;
use App\Models\SyncReplayItem;
use App\Support\ApiResponse;
use App\Support\ScopeAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ScopeAccess $scopeAccess,
    ) {}

    /**
     * Return the member home summary contract for the authenticated member.
     */
    public function memberHome(Request $request): JsonResponse
    {
        $actor = $request->user();
        $member = $this->scopeAccess->linkedMember($actor);

        if ($member === null) {
            return ApiResponse::error(
                message: __('api.errors.member_profile_not_found'),
                code: 'member_profile_not_found',
                status: 404,
            );
        }

        $today = now()->toDateString();
        $sevenDayStart = now()->subDays(6)->toDateString();

        $milkQuery = MilkProductionLog::query()->where('member_id', $member->id);

        $todayQuantity = (float) ((clone $milkQuery)
            ->whereDate('production_date', $today)
            ->sum('quantity_liters'));

        $sevenDayAggregate = (clone $milkQuery)
            ->whereDate('production_date', '>=', $sevenDayStart)
            ->selectRaw('COUNT(*) as entries_count, COALESCE(SUM(quantity_liters), 0) as total_quantity_liters')
            ->first();

        $syncCounts = SyncReplayItem::query()
            ->where('actor_id', $actor->id)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $recentActivity = (clone $milkQuery)
            ->latest('production_date')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (MilkProductionLog $log): array => [
                'id' => $log->id,
                'quantity_liters' => (float) $log->quantity_liters,
                'production_date' => $log->production_date?->toDateString(),
                'status' => $log->status,
                'source' => $log->source,
                'created_at' => $log->created_at?->toIso8601String(),
            ])
            ->all();

        $latestNotifications = $actor->notifications()
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($notification): array => [
                'id' => $notification->id,
                'type' => (string) data_get($notification->data, 'type', 'system'),
                'title' => (string) data_get($notification->data, 'title', ''),
                'message' => (string) data_get($notification->data, 'message', ''),
                'payload' => data_get($notification->data, 'payload', []),
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ])
            ->all();

        $lastSync = SyncReplayItem::query()
            ->where('actor_id', $actor->id)
            ->latest('processed_at')
            ->first();

        return ApiResponse::success([
            'member' => [
                'id' => $member->id,
                'cooperative_id' => $member->cooperative_id,
                'cluster_id' => $member->cluster_id,
                'member_number' => $member->member_number,
                'first_name' => $member->first_name,
                'last_name' => $member->last_name,
                'status' => $member->status,
            ],
            'kpis' => [
                'today_quantity_liters' => $todayQuantity,
                'seven_day_total_quantity_liters' => (float) ($sevenDayAggregate?->total_quantity_liters ?? 0),
                'seven_day_entries_count' => (int) ($sevenDayAggregate?->entries_count ?? 0),
                'pending_sync_items' => (int) ($syncCounts->get('failed_sync', 0) + $syncCounts->get('blocked', 0) + $syncCounts->get('rejected', 0)),
                'unread_notifications' => $actor->unreadNotifications()->count(),
            ],
            'recent_activity' => $recentActivity,
            'sync_summary' => [
                'counts' => [
                    'synced' => (int) $syncCounts->get('synced', 0),
                    'duplicate' => (int) $syncCounts->get('duplicate', 0),
                    'conflict' => (int) $syncCounts->get('conflict', 0),
                    'rejected' => (int) $syncCounts->get('rejected', 0),
                    'blocked' => (int) $syncCounts->get('blocked', 0),
                    'failed_sync' => (int) $syncCounts->get('failed_sync', 0),
                ],
                'last_processed_at' => $lastSync?->processed_at?->toIso8601String(),
            ],
            'latest_notifications' => $latestNotifications,
        ]);
    }

    /**
     * Return the scoped admin dashboard summary contract.
     */
    public function adminSummary(Request $request): JsonResponse
    {
        $actor = $request->user();
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

        $todayMilkLiters = (float) ((clone $milkQuery)
            ->whereDate('production_date', now()->toDateString())
            ->sum('quantity_liters'));

        $sevenDayMilkLiters = (float) ((clone $milkQuery)
            ->whereDate('production_date', '>=', now()->subDays(6)->toDateString())
            ->sum('quantity_liters'));

        $pendingAssignments = (int) (clone $assignmentQuery)->count();
        $unresolvedConflicts = (int) (clone $syncConflictQuery)->count();

        return ApiResponse::success([
            'scope_summary' => $this->scopeAccess->summary($actor),
            'kpis' => [
                'active_cooperatives' => (int) (clone $cooperativeQuery)->where('status', 'active')->count(),
                'active_clusters' => (int) (clone $clusterQuery)->where('status', 'active')->count(),
                'active_members' => (int) (clone $memberQuery)->where('status', 'active')->count(),
                'today_milk_volume_liters' => $todayMilkLiters,
                'seven_day_milk_volume_liters' => $sevenDayMilkLiters,
            ],
            'exceptions' => [
                [
                    'code' => 'pending_member_assignments',
                    'count' => $pendingAssignments,
                ],
                [
                    'code' => 'unresolved_sync_conflicts',
                    'count' => $unresolvedConflicts,
                ],
            ],
            'approvals' => [
                [
                    'type' => 'member_assignments',
                    'count' => $pendingAssignments,
                ],
            ],
        ]);
    }
}
