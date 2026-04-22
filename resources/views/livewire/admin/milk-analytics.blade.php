<div class="space-y-6">
    <section class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-cyan-200/70">Analytics</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-white">Milk totals and trend charts</h2>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-stone-300">Monitor daily milk volume across your accessible scope with interactive range and location filters. The chart is server-authoritative and updates without a full page reload.</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.25em] text-stone-400">Window</span>
                    <select wire:model.live="days" class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-white focus:border-cyan-300/50 focus:outline-none focus:ring-2 focus:ring-cyan-300/20">
                        <option value="7">Last 7 days</option>
                        <option value="14">Last 14 days</option>
                        <option value="30">Last 30 days</option>
                        <option value="60">Last 60 days</option>
                        <option value="90">Last 90 days</option>
                    </select>
                </label>

                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.25em] text-stone-400">Cooperative</span>
                    <select wire:model.live="cooperativeId" class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-white focus:border-cyan-300/50 focus:outline-none focus:ring-2 focus:ring-cyan-300/20">
                        <option value="">All accessible cooperatives</option>
                        @foreach ($cooperatives as $cooperative)
                            <option value="{{ $cooperative->id }}">{{ $cooperative->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.25em] text-stone-400">Cluster</span>
                    <select wire:model.live="clusterId" class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-white focus:border-cyan-300/50 focus:outline-none focus:ring-2 focus:ring-cyan-300/20">
                        <option value="">All accessible clusters</option>
                        @foreach ($clusters as $cluster)
                            <option value="{{ $cluster->id }}">{{ $cluster->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <article class="rounded-[2rem] border border-white/10 bg-white/5 p-5 backdrop-blur">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-stone-500">{{ $kpi['label'] }}</p>
                <p class="mt-4 text-3xl font-semibold tracking-tight text-white">{{ $kpi['value'] }}</p>
            </article>
        @endforeach
    </section>

    <div wire:loading.delay.shorter wire:target="days,cooperativeId,clusterId" class="rounded-3xl border border-cyan-300/30 bg-cyan-400/10 px-4 py-3 text-sm text-cyan-100">
        Refreshing analytics view...
    </div>

    <section class="grid gap-6 xl:grid-cols-[1.4fr_0.6fr]">
        <article wire:loading.class="opacity-60" wire:target="days,cooperativeId,clusterId" class="overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur transition-opacity">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.3em] text-cyan-200/70">Trend</p>
                    <h3 class="mt-2 text-2xl font-semibold text-white">Daily milk production</h3>
                </div>
                <p class="text-sm text-stone-400">Each bar reflects accepted production logs for the selected day.</p>
            </div>

            @if ($chartBars->contains(fn ($bar) => $bar['total_liters'] > 0))
                <div class="mt-6 overflow-x-auto pb-2">
                    <svg viewBox="0 0 {{ $chartWidth }} 260" class="min-w-[720px]">
                        <line x1="20" y1="212" x2="{{ $chartWidth - 20 }}" y2="212" stroke="rgba(255,255,255,0.12)" stroke-width="1" />
                        @foreach ($chartBars as $bar)
                            <rect x="{{ $bar['x'] }}" y="{{ $bar['y'] }}" width="{{ $bar['width'] }}" height="{{ max(4, $bar['height']) }}" rx="14" fill="rgba(103,232,249,0.8)" />
                            <text x="{{ $bar['x'] + ($bar['width'] / 2) }}" y="236" text-anchor="middle" fill="rgba(214, 211, 209, 0.92)" font-size="12">{{ $bar['label'] }}</text>
                            <text x="{{ $bar['x'] + ($bar['width'] / 2) }}" y="{{ max(20, $bar['y'] - 8) }}" text-anchor="middle" fill="rgba(236, 254, 255, 0.95)" font-size="12">{{ number_format($bar['total_liters'], 1) }}</text>
                        @endforeach
                    </svg>
                </div>
            @else
                <div class="mt-6 rounded-3xl border border-white/10 bg-black/20 p-6 text-center">
                    <p class="text-sm uppercase tracking-[0.25em] text-stone-500">No chart data</p>
                    <p class="mt-3 text-base font-medium text-stone-200">No accepted milk logs were found for the selected filters.</p>
                </div>
            @endif
        </article>

        <article class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-200/70">Peak Day</p>
            <h3 class="mt-2 text-2xl font-semibold text-white">{{ $peakDay['label'] ?? 'No activity yet' }}</h3>
            <p class="mt-3 text-sm leading-6 text-stone-300">
                @if ($peakDay)
                    {{ number_format($peakDay['total_liters'], 1) }} liters were logged on the highest-volume day in the current window.
                @else
                    No milk logs are available in the current filter range.
                @endif
            </p>

            <div class="mt-6 rounded-3xl border border-white/10 bg-black/20 p-5">
                <p class="text-xs uppercase tracking-[0.25em] text-stone-500">How to use this view</p>
                <ul class="mt-3 space-y-2 text-sm leading-6 text-stone-300">
                    <li>Switch the window to compare short-term spikes against longer production trends.</li>
                    <li>Filter by cooperative or cluster to investigate local output issues without leaving the dashboard.</li>
                    <li>Use this page before reviewing approvals when daily totals look unexpectedly low or high.</li>
                </ul>
            </div>
        </article>
    </section>
</div>
