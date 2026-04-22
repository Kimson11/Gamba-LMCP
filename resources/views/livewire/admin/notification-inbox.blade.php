<div class="space-y-6">
    <section class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-cyan-200/70">Inbox</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-white">Operational notifications</h2>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-stone-300">Filter the admin inbox by read state, delivery outcome, or free-text search without leaving the page. Marking a notification as read updates instantly.</p>
            </div>
            <p class="inline-flex w-fit rounded-full bg-cyan-400/15 px-4 py-2 text-sm font-medium text-cyan-100">{{ $unreadCount }} unread</p>
        </div>
    </section>

    <section class="grid gap-3 rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur md:grid-cols-3">
        <label class="space-y-2">
            <span class="text-xs font-semibold uppercase tracking-[0.25em] text-stone-400">Read state</span>
            <select wire:model.live="statusFilter" class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-white focus:border-cyan-300/50 focus:outline-none focus:ring-2 focus:ring-cyan-300/20">
                <option value="all">All notifications</option>
                <option value="unread">Unread only</option>
                <option value="read">Read only</option>
            </select>
        </label>

        <label class="space-y-2">
            <span class="text-xs font-semibold uppercase tracking-[0.25em] text-stone-400">Delivery status</span>
            <select wire:model.live="deliveryFilter" class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-white focus:border-cyan-300/50 focus:outline-none focus:ring-2 focus:ring-cyan-300/20">
                <option value="all">All delivery states</option>
                @foreach ($deliveryStatuses as $deliveryStatus)
                    <option value="{{ $deliveryStatus }}">{{ str($deliveryStatus)->headline() }}</option>
                @endforeach
            </select>
        </label>

        <label class="space-y-2">
            <span class="text-xs font-semibold uppercase tracking-[0.25em] text-stone-400">Search</span>
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Title, message, or type" class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-white placeholder:text-stone-500 focus:border-cyan-300/50 focus:outline-none focus:ring-2 focus:ring-cyan-300/20" />
        </label>
    </section>

    <div wire:loading.delay.shorter wire:target="statusFilter,deliveryFilter,search,markRead" class="rounded-3xl border border-cyan-300/30 bg-cyan-400/10 px-4 py-3 text-sm text-cyan-100">
        Refreshing inbox...
    </div>

    <section class="space-y-4">
        @forelse ($notifications as $notification)
            @php($delivery = $deliveryRows->get($notification->id))
            <article wire:key="notification-{{ $notification->id }}" class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] {{ $notification->read_at ? 'bg-white/10 text-stone-300' : 'bg-amber-400/15 text-amber-100' }}">
                                {{ $notification->read_at ? 'Read' : 'Unread' }}
                            </span>
                            <span class="rounded-full bg-cyan-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] text-cyan-100">
                                {{ data_get($notification->data, 'type', 'system') }}
                            </span>
                        </div>

                        <div>
                            <h3 class="text-2xl font-semibold text-white">{{ data_get($notification->data, 'title', 'Notification') }}</h3>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-300">{{ data_get($notification->data, 'message', '') }}</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="rounded-3xl border border-white/10 bg-black/20 p-4">
                                <p class="text-xs uppercase tracking-[0.25em] text-stone-500">Created</p>
                                <p class="mt-2 text-sm font-medium text-white">{{ $notification->created_at?->diffForHumans() }}</p>
                            </div>
                            <div class="rounded-3xl border border-white/10 bg-black/20 p-4">
                                <p class="text-xs uppercase tracking-[0.25em] text-stone-500">Delivery</p>
                                <p class="mt-2 text-sm font-medium text-white">{{ str($delivery?->status ?? 'unknown')->headline() }}</p>
                            </div>
                            <div class="rounded-3xl border border-white/10 bg-black/20 p-4">
                                <p class="text-xs uppercase tracking-[0.25em] text-stone-500">Delivered</p>
                                <p class="mt-2 text-sm font-medium text-white">{{ $delivery?->delivered_at?->diffForHumans() ?? 'Pending' }}</p>
                            </div>
                        </div>
                    </div>

                    @if (! $notification->read_at)
                        <button wire:click="markRead('{{ $notification->id }}')" wire:loading.attr="disabled" wire:target="markRead('{{ $notification->id }}')" type="button" class="inline-flex items-center justify-center rounded-2xl border border-white/10 px-4 py-3 text-sm font-semibold text-stone-200 transition hover:border-white/20 hover:bg-white/5 hover:text-white disabled:cursor-not-allowed disabled:opacity-60">
                            <span wire:loading.remove wire:target="markRead('{{ $notification->id }}')">Mark read</span>
                            <span wire:loading wire:target="markRead('{{ $notification->id }}')">Updating...</span>
                        </button>
                    @endif
                </div>
            </article>
        @empty
            <article class="rounded-[2rem] border border-white/10 bg-white/5 p-10 text-center backdrop-blur">
                <p class="text-sm uppercase tracking-[0.25em] text-stone-500">Quiet shift</p>
                <p class="mt-3 text-xl font-semibold text-white">No notifications match the current filters.</p>
            </article>
        @endforelse
    </section>

    {{ $notifications->links() }}
</div>
