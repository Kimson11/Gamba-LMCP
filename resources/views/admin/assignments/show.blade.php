<x-admin.layout title="Assignment Detail">
    <div class="space-y-6">
        <section class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.3em] text-amber-200/70">Approval Detail</p>
                    <h2 class="mt-2 text-3xl font-semibold tracking-tight text-white">
                        {{ $assignment->member?->first_name }} {{ $assignment->member?->last_name }}
                    </h2>
                    <p class="mt-2 text-sm text-stone-300">Member #{{ $assignment->member?->member_number ?? $assignment->member_id }}</p>
                </div>

                <a
                    href="{{ route('admin.assignments.history') }}"
                    class="inline-flex items-center justify-center rounded-full border border-white/10 px-4 py-2 text-sm font-medium text-stone-200 transition hover:border-white/20 hover:bg-white/5 hover:text-white"
                >
                    Back to history
                </a>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <article class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                <p class="text-xs uppercase tracking-[0.25em] text-stone-500">Status</p>
                <p class="mt-2 text-sm font-semibold text-white">{{ str($assignment->approval_status)->headline() }}</p>
            </article>
            <article class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                <p class="text-xs uppercase tracking-[0.25em] text-stone-500">From cluster</p>
                <p class="mt-2 text-sm font-semibold text-white">{{ $assignment->fromCluster?->name ?? 'Unassigned' }}</p>
            </article>
            <article class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                <p class="text-xs uppercase tracking-[0.25em] text-stone-500">To cluster</p>
                <p class="mt-2 text-sm font-semibold text-white">{{ $assignment->toCluster?->name ?? 'Unknown cluster' }}</p>
            </article>
            <article class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                <p class="text-xs uppercase tracking-[0.25em] text-stone-500">Submitted</p>
                <p class="mt-2 text-sm font-semibold text-white">{{ $assignment->created_at?->format('M j, Y g:i A') }}</p>
            </article>
        </section>

        @if ($assignment->rejection_reason)
            <section class="rounded-3xl border border-rose-300/20 bg-rose-400/10 p-5">
                <p class="text-xs uppercase tracking-[0.25em] text-rose-100/80">Rejection reason</p>
                <p class="mt-2 text-sm leading-6 text-rose-50">{{ $assignment->rejection_reason }}</p>
            </section>
        @endif

        <section class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-stone-500">Approval timeline</p>
            <div class="mt-5 space-y-4">
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
        </section>
    </div>
</x-admin.layout>