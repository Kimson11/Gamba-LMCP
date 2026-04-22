<x-admin.layout title="Pending Approvals">
    <div class="space-y-6">
        <section class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.3em] text-amber-200/70">Queue</p>
                    <h2 class="mt-2 text-3xl font-semibold tracking-tight text-white">Member assignment approvals</h2>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <p class="text-sm text-stone-300">{{ $assignments->total() }} pending item{{ $assignments->total() === 1 ? '' : 's' }}</p>
                    <a href="{{ route('admin.assignments.history') }}" class="inline-flex items-center justify-center rounded-full border border-white/10 px-4 py-2 text-sm font-medium text-stone-200 transition hover:border-white/20 hover:bg-white/5 hover:text-white">Open history</a>
                </div>
            </div>
        </section>

        <section class="space-y-4">
            @forelse ($assignments as $assignment)
                <article class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur">
                    <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs uppercase tracking-[0.25em] text-stone-500">Member</p>
                                <h3 class="mt-2 text-2xl font-semibold text-white">
                                    {{ $assignment->member?->first_name }} {{ $assignment->member?->last_name }}
                                </h3>
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
                                    <p class="text-xs uppercase tracking-[0.25em] text-stone-500">Submitted</p>
                                    <p class="mt-2 text-sm font-medium text-white">{{ $assignment->created_at?->diffForHumans() }}</p>
                                </div>
                            </div>

                            <div class="rounded-3xl border border-white/10 bg-black/20 p-4">
                                <p class="text-xs uppercase tracking-[0.25em] text-stone-500">Reason</p>
                                <p class="mt-2 text-sm leading-6 text-stone-200">{{ $assignment->reason ?: 'No reason was supplied.' }}</p>
                            </div>
                        </div>

                        <div class="grid gap-3 xl:w-[22rem]">
                            <form method="POST" action="{{ route('admin.assignments.approve', $assignment) }}">
                                @csrf
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-400 px-4 py-3 text-sm font-semibold text-stone-950 transition hover:bg-emerald-300">
                                    Approve assignment
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.assignments.reject', $assignment) }}" class="space-y-3 rounded-3xl border border-white/10 bg-black/20 p-4">
                                @csrf
                                <label for="reason-{{ $assignment->id }}" class="text-sm font-medium text-stone-200">Reject with reason</label>
                                <textarea id="reason-{{ $assignment->id }}" name="reason" rows="4" class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white placeholder:text-stone-500 focus:border-rose-300/60 focus:outline-none focus:ring-2 focus:ring-rose-300/20" placeholder="State why this request cannot be approved."></textarea>
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl border border-rose-300/30 bg-rose-400/10 px-4 py-3 text-sm font-semibold text-rose-100 transition hover:border-rose-200/40 hover:bg-rose-400/20">
                                    Reject assignment
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <article class="rounded-[2rem] border border-white/10 bg-white/5 p-10 text-center backdrop-blur">
                    <p class="text-sm uppercase tracking-[0.25em] text-stone-500">Queue is clear</p>
                    <p class="mt-3 text-xl font-semibold text-white">No pending assignments right now.</p>
                </article>
            @endforelse
        </section>

        {{ $assignments->links() }}
    </div>
</x-admin.layout>
