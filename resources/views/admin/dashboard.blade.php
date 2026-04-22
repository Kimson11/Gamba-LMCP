<x-admin.layout title="Dashboard">
    <div class="space-y-8">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($stats as $stat)
                <article class="overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 backdrop-blur">
                    <div class="h-2 w-full" style="{{ $stat['accent_style'] }}"></div>
                    <div class="space-y-3 p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-stone-400">{{ $stat['label'] }}</p>
                        <p class="text-3xl font-semibold tracking-tight text-white">{{ $stat['value'] }}</p>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <article class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.3em] text-amber-200/70">Scope</p>
                        <h2 class="mt-2 text-2xl font-semibold text-white">Current operating footprint</h2>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-3xl border border-white/10 bg-black/20 p-5">
                        <p class="text-xs uppercase tracking-[0.25em] text-stone-500">Cooperatives</p>
                        <p class="mt-3 text-xl font-semibold text-white">{{ count($scopeSummary['cooperative_ids']) }}</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-black/20 p-5">
                        <p class="text-xs uppercase tracking-[0.25em] text-stone-500">Clusters</p>
                        <p class="mt-3 text-xl font-semibold text-white">{{ count($scopeSummary['cluster_ids']) }}</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-black/20 p-5">
                        <p class="text-xs uppercase tracking-[0.25em] text-stone-500">Open sync conflicts</p>
                        <p class="mt-3 text-xl font-semibold text-white">{{ $unresolvedConflicts }}</p>
                    </div>
                </div>
            </article>

            <article class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur">
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-rose-200/70">Exceptions</p>
                <h2 class="mt-2 text-2xl font-semibold text-white">Review queue</h2>

                <div class="mt-6 space-y-3">
                    @foreach ($exceptions as $exception)
                        <div class="rounded-3xl border border-white/10 bg-black/20 p-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium text-stone-200">{{ $exception['label'] }}</p>
                                    <p class="mt-1 text-xs uppercase tracking-[0.25em] text-stone-500">Needs attention</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="rounded-full bg-rose-400/20 px-3 py-1 text-sm font-semibold text-rose-100">{{ $exception['count'] }}</span>
                                    @if ($exception['href'])
                                        <a href="{{ $exception['href'] }}" class="rounded-full border border-white/10 px-3 py-1 text-sm text-stone-200 transition hover:border-white/20 hover:bg-white/5 hover:text-white">Open</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <article class="rounded-[2rem] border border-cyan-300/20 bg-cyan-400/10 p-6 backdrop-blur">
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-cyan-100/80">Analytics</p>
                <h2 class="mt-3 text-2xl font-semibold text-white">Milk totals and trend charts</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-cyan-50/85">Open the new analytics workspace for date-range filtering, scoped cooperative and cluster views, and trend monitoring across the latest milk submissions.</p>
                <a href="{{ route('admin.analytics.index') }}" class="mt-5 inline-flex items-center justify-center rounded-full bg-cyan-300 px-4 py-2 text-sm font-semibold text-stone-950 transition hover:bg-cyan-200">Open analytics</a>
            </article>

            <article class="rounded-[2rem] border border-amber-300/20 bg-amber-400/10 p-6 backdrop-blur">
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-amber-100/80">Approval History</p>
                <h2 class="mt-3 text-2xl font-semibold text-white">Review past decisions and event timelines</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-amber-50/85">The approval history view now includes search, status filters, and append-only event timelines so reviewers can audit past assignment decisions without leaving the admin portal.</p>
                <a href="{{ route('admin.assignments.history') }}" class="mt-5 inline-flex items-center justify-center rounded-full bg-amber-300 px-4 py-2 text-sm font-semibold text-stone-950 transition hover:bg-amber-200">Open history</a>
            </article>
        </section>
    </div>
</x-admin.layout>
