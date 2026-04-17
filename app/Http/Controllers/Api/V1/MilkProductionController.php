<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateMilkProductionLogRequest;
use App\Models\Cluster;
use App\Models\Member;
use App\Models\MilkProductionLog;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MilkProductionController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Create one milk production record.
     */
    public function store(CreateMilkProductionLogRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $actor = $request->user();
        $member = Member::query()->findOrFail((int) $validated['member_id']);

        $log = MilkProductionLog::query()->create([
            'cooperative_id' => $member->cooperative_id,
            'cluster_id' => (int) $validated['cluster_id'],
            'member_id' => $member->id,
            'recorded_by_user_id' => $actor?->id,
            'quantity_liters' => $validated['quantity_liters'],
            'production_date' => $validated['production_date'],
            'source' => $validated['source'] ?? 'mobile',
            'status' => 'recorded',
        ]);

        $this->auditLogger->record(
            action: 'milk_production.recorded',
            subject: $log,
            actor: $actor,
            cooperativeId: (int) $member->cooperative_id,
            context: [
                'member_id' => $member->id,
                'cluster_id' => $log->cluster_id,
                'quantity_liters' => $log->quantity_liters,
                'production_date' => $log->production_date?->toDateString(),
            ],
        );

        return ApiResponse::success([
            'id' => $log->id,
            'cooperative_id' => $log->cooperative_id,
            'cluster_id' => $log->cluster_id,
            'member_id' => $log->member_id,
            'recorded_by_user_id' => $log->recorded_by_user_id,
            'quantity_liters' => (float) $log->quantity_liters,
            'production_date' => $log->production_date?->toDateString(),
            'source' => $log->source,
            'status' => $log->status,
            'created_at' => $log->created_at?->toIso8601String(),
        ], 201);
    }

    /**
     * List milk production logs for one cluster.
     */
    public function indexByCluster(Cluster $cluster): JsonResponse
    {
        $logs = MilkProductionLog::query()
            ->where('cluster_id', $cluster->id)
            ->orderByDesc('production_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (MilkProductionLog $log): array => [
                'id' => $log->id,
                'cooperative_id' => $log->cooperative_id,
                'cluster_id' => $log->cluster_id,
                'member_id' => $log->member_id,
                'quantity_liters' => (float) $log->quantity_liters,
                'production_date' => $log->production_date?->toDateString(),
                'source' => $log->source,
                'status' => $log->status,
                'created_at' => $log->created_at?->toIso8601String(),
            ])
            ->all();

        return ApiResponse::success([
            'cluster_id' => $cluster->id,
            'items' => $logs,
        ]);
    }

    /**
     * Aggregate daily milk totals for one cluster.
     */
    public function dailyTotalsByCluster(Request $request, Cluster $cluster): JsonResponse
    {
        $validated = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $fromDate = (string) ($validated['from_date'] ?? now()->subDays(6)->toDateString());
        $toDate = (string) ($validated['to_date'] ?? now()->toDateString());

        $dailyTotals = MilkProductionLog::query()
            ->where('cluster_id', $cluster->id)
            ->whereDate('production_date', '>=', $fromDate)
            ->whereDate('production_date', '<=', $toDate)
            ->selectRaw('DATE(production_date) as production_date, COUNT(*) as entries_count, SUM(quantity_liters) as total_quantity_liters')
            ->groupByRaw('DATE(production_date)')
            ->orderBy('production_date')
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'production_date' => (string) $row->production_date,
                'entries_count' => (int) ($row->entries_count ?? 0),
                'total_quantity_liters' => (float) ($row->total_quantity_liters ?? 0),
            ])
            ->all();

        return ApiResponse::success([
            'cluster_id' => $cluster->id,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'items' => $dailyTotals,
        ]);
    }

    /**
     * Aggregate daily milk totals for one member.
     */
    public function dailyTotalsByMember(Request $request, Member $member): JsonResponse
    {
        $validated = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $fromDate = (string) ($validated['from_date'] ?? now()->subDays(6)->toDateString());
        $toDate = (string) ($validated['to_date'] ?? now()->toDateString());

        $dailyTotals = MilkProductionLog::query()
            ->where('member_id', $member->id)
            ->whereDate('production_date', '>=', $fromDate)
            ->whereDate('production_date', '<=', $toDate)
            ->selectRaw('DATE(production_date) as production_date, COUNT(*) as entries_count, SUM(quantity_liters) as total_quantity_liters')
            ->groupByRaw('DATE(production_date)')
            ->orderBy('production_date')
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'production_date' => (string) $row->production_date,
                'entries_count' => (int) ($row->entries_count ?? 0),
                'total_quantity_liters' => (float) ($row->total_quantity_liters ?? 0),
            ])
            ->all();

        return ApiResponse::success([
            'member_id' => $member->id,
            'cluster_id' => $member->cluster_id,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'items' => $dailyTotals,
        ]);
    }
}
