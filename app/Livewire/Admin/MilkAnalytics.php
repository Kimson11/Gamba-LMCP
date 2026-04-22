<?php

namespace App\Livewire\Admin;

use App\Models\Cluster;
use App\Models\Cooperative;
use App\Models\MilkProductionLog;
use App\Models\User;
use App\Support\ScopeAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Component;

class MilkAnalytics extends Component
{
    public int $days = 14;

    public ?int $cooperativeId = null;

    public ?int $clusterId = null;

    public function updatedDays(): void
    {
        $this->days = max(7, min(90, $this->days));
    }

    public function updatedCooperativeId(): void
    {
        $this->clusterId = null;
    }

    public function render(): View
    {
        $actor = auth()->user();

        abort_unless($actor instanceof User, 401);

        $scopeAccess = app(ScopeAccess::class);

        $cooperatives = Cooperative::query()
            ->where('status', 'active')
            ->tap(fn ($query) => $scopeAccess->applyCooperativeScope($actor, $query))
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($this->cooperativeId !== null && ! $cooperatives->contains('id', $this->cooperativeId)) {
            $this->cooperativeId = null;
        }

        $clusters = Cluster::query()
            ->where('status', 'active')
            ->tap(fn ($query) => $scopeAccess->applyClusterScope($actor, $query))
            ->when($this->cooperativeId !== null, fn ($query) => $query->where('cooperative_id', $this->cooperativeId))
            ->orderBy('name')
            ->get(['id', 'name', 'cooperative_id']);

        if ($this->clusterId !== null && ! $clusters->contains('id', $this->clusterId)) {
            $this->clusterId = null;
        }

        $startDate = now()->startOfDay()->subDays($this->days - 1);

        $baseQuery = MilkProductionLog::query()
            ->whereDate('production_date', '>=', $startDate->toDateString());

        $scopeAccess->applyCooperativeScope($actor, $baseQuery, 'cooperative_id');

        if ($this->cooperativeId !== null) {
            $baseQuery->where('cooperative_id', $this->cooperativeId);
        }

        if ($this->clusterId !== null) {
            $baseQuery->where('cluster_id', $this->clusterId);
        }

        $dailyAggregate = (clone $baseQuery)
            ->selectRaw('production_date, SUM(quantity_liters) as total_liters')
            ->groupBy('production_date')
            ->orderBy('production_date')
            ->get()
            ->mapWithKeys(fn ($row): array => [
                Carbon::parse($row->production_date)->toDateString() => (float) $row->total_liters,
            ]);

        $dailySeries = collect(range(0, $this->days - 1))
            ->map(function (int $offset) use ($startDate, $dailyAggregate): array {
                $date = $startDate->copy()->addDays($offset);
                $dateKey = $date->toDateString();

                return [
                    'label' => $date->format('M j'),
                    'date' => $dateKey,
                    'total_liters' => (float) ($dailyAggregate[$dateKey] ?? 0),
                ];
            });

        $maxTotal = max(1, (float) $dailySeries->max('total_liters'));
        $barWidth = 40;
        $barGap = 18;

        $chartBars = $dailySeries->values()->map(function (array $point, int $index) use ($maxTotal, $barWidth, $barGap): array {
            $height = (int) round(($point['total_liters'] / $maxTotal) * 180);

            return [
                'label' => $point['label'],
                'total_liters' => $point['total_liters'],
                'x' => 24 + ($index * ($barWidth + $barGap)),
                'y' => 212 - $height,
                'height' => $height,
                'width' => $barWidth,
            ];
        });

        $totalLiters = (float) $dailySeries->sum('total_liters');
        $averageLiters = $this->days > 0 ? $totalLiters / $this->days : 0;
        $peakDay = $dailySeries->sortByDesc('total_liters')->first();

        $kpis = [
            [
                'label' => 'Total volume',
                'value' => number_format($totalLiters, 1).' L',
            ],
            [
                'label' => 'Daily average',
                'value' => number_format($averageLiters, 1).' L',
            ],
            [
                'label' => 'Active members',
                'value' => number_format((int) (clone $baseQuery)->distinct('member_id')->count('member_id')),
            ],
            [
                'label' => 'Active clusters',
                'value' => number_format((int) (clone $baseQuery)->distinct('cluster_id')->count('cluster_id')),
            ],
        ];

        return view('livewire.admin.milk-analytics', [
            'cooperatives' => $cooperatives,
            'clusters' => $clusters,
            'chartBars' => $chartBars,
            'chartWidth' => max(720, 48 + ($chartBars->count() * ($barWidth + $barGap))),
            'kpis' => $kpis,
            'peakDay' => $peakDay,
        ]);
    }
}
