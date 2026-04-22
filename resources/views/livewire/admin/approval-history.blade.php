<div class="space-y-6">
    <section class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-amber-200/70">History</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-white">Approval decision timeline</h2>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-stone-300">Review resolved and in-flight member assignment decisions with their append-only approval events. This page is read-focused so reviewers can investigate before taking action in the pending queue.</p>
            </div>
        </div>
    </section>

    <section class="grid gap-3 rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur md:grid-cols-[0.35fr_0.65fr]">
        <label class="space-y-2">
            <span class="text-xs font-semibold uppercase tracking-[0.25em] text-stone-400">Status</span>
            <select wire:model.live="statusFilter" class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-white focus:border-amber-300/50 focus:outline-none focus:ring-2 focus:ring-amber-300/20">
                <option value="resolved">Resolved only</option>
                <option value="approved">Approved only</option>
                <option value="rejected">Rejected only</option>
                <option value="pending">Pending only</option>
                <option value="all">All assignments</option>
            </select>
        </label>

        <label class="space-y-2">
            <span class="text-xs font-semibold uppercase tracking-[0.25em] text-stone-400">Search</span>
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Member name or member number" class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-white placeholder:text-stone-500 focus:border-amber-300/50 focus:outline-none focus:ring-2 focus:ring-amber-300/20" />
        </label>
    </section>

    <div wire:loading.delay.shorter wire:target="statusFilter,search,gotoPage,nextPage,previousPage" class="rounded-3xl border border-amber-300/30 bg-amber-400/10 px-4 py-3 text-sm text-amber-100">
        Refreshing approval history...
    </div>

    <section class="space-y-4">
        @forelse ($assignments as $assignment)
            @php($timeline = $timelines->get($assignment->id, collect()))
            <article wire:key="assignment-history-{{ $assignment->id }}" class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] {{ $assignment->approval_status === 'approved' ? 'bg-emerald-400/15 text-emerald-100' : ($assignment->approval_status === 'rejected' ? 'bg-rose-400/15 text-rose-100' : 'bg-amber-400/15 text-amber-100') }}">
                                {{ str($assignment->approval_status)->headline() }}
                            </span>
                            <span class="text-xs uppercase tracking-[0.25em] text-stone-500">Submitted {{ $assignment->created_at?->diffForHumans() }}</span>
                        </div>

                        <div>
                            <h3 class="text-2xl font-semibold text-white">{{ $assignment->member?->first_name }} {{ $assignment->member?->last_name }}</h3>
                            <p class="mt-1 text-sm text-stone-300">#{{ $assignment->member?->member_number ?? $assignment->member_id }}</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-3xl border border-white/10 bg-black/20 p-4">
                                <p class="text-xs uppercase tracking-[0.25em] text-stone-500">From</p>
                                <p class="mt-2 text-sm font-medium text-white">{{ $assignment->fromCluster?->name ?? 'Unassigned' }}</p>
                            </div>
                            <div class="rounded-3xl border border-white/10 bg-black/20 p-4">
                                <p class="text-xs uppercase tracking-[0.25em] text-stone-500">To</p>
                                <p class="mt-2 text-sm font-medium text-white">{{ $assignment->toCluster?->name ?? 'Unknown cluster' }}</p>
                            </div>
                            <div class="rounded-3xl border border-white/10 bg-black/20 p-4">
                                <p class="text-xs uppercase tracking-[0.25em] text-stone-500">Reviewed by</p>
                                <p class="mt-2 text-sm font-medium text-white">{{ $assignment->approvedBy?->name ?? $assignment->rejectedBy?->name ?? 'Pending review' }}</p>
                            </div>
                        </div>

                        @if ($assignment->rejection_reason)
                            <div class="rounded-3xl border border-rose-300/20 bg-rose-400/10 p-4">
                                <p class="text-xs uppercase tracking-[0.25em] text-rose-100/80">Rejection reason</p>
                                <p class="mt-2 text-sm leading-6 text-rose-50">{{ $assignment->rejection_reason }}</p>
                            </div>
                        @endif

                        <a href="{{ route('admin.assignments.history.show', $assignment) }}" class="inline-flex items-center justify-center rounded-full border border-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-stone-200 transition hover:border-white/20 hover:bg-white/5 hover:text-white">
                            Open detail view
                        </a>
                    </div>

                    <div class="xl:w-[26rem]">
                        <div class="rounded-3xl border border-white/10 bg-black/20 p-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-stone-500">Approval timeline</p>
                            <div class="mt-4 space-y-4">
                                @forelse ($timeline as $event)
                                    <div class="border-l border-white/10 pl-4">
                                        <p class="text-sm font-semibold text-white">{{ str($event->event_type->value)->headline() }}</p>
                                        <p class="mt-1 text-xs uppercase tracking-[0.2em] text-stone-500">{{ $event->created_at?->format('M j, Y g:i A') }}</p>
                                        <p class="mt-2 text-sm text-stone-300">Actor: {{ $event->actor?->name ?? 'System' }} · {{ str($event->actor_role ?? 'unknown')->headline() }}</p>
                                        @if ($event->reason)
                                            <p class="mt-2 text-sm text-stone-300">Reason: {{ $event->reason }}</p>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-sm text-stone-400">No approval events recorded yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <article class="rounded-[2rem] border border-white/10 bg-white/5 p-10 text-center backdrop-blur">
                <p class="text-sm uppercase tracking-[0.25em] text-stone-500">No results</p>
                <p class="mt-3 text-xl font-semibold text-white">No approval history matches the current filters.</p>
            </article>
        @endforelse
    </section>

    {{ $assignments->links() }}
</div>
