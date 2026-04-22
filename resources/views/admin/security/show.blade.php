<x-admin.layout title="Security Check">
    <div class="mx-auto max-w-5xl space-y-8">
        <section class="rounded-[2.5rem] border border-white/10 bg-white/5 p-8 backdrop-blur">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-amber-200/70">Privileged Session</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white">Elevate this browser session</h2>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-stone-300">
                Privileged roles need MFA confirmation and an active trusted device before accessing dashboard summaries and approval actions. This matches the same control boundary enforced on the API.
            </p>
        </section>

        <section class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
            <article class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur">
                <h3 class="text-xl font-semibold text-white">Status</h3>
                <dl class="mt-6 space-y-4">
                    <div class="rounded-3xl border border-white/10 bg-black/20 p-4">
                        <dt class="text-xs uppercase tracking-[0.25em] text-stone-500">MFA enabled</dt>
                        <dd class="mt-2 text-lg font-semibold text-white">{{ auth()->user()?->mfa_enabled ? 'Yes' : 'No' }}</dd>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-black/20 p-4">
                        <dt class="text-xs uppercase tracking-[0.25em] text-stone-500">Active trusted devices</dt>
                        <dd class="mt-2 text-lg font-semibold text-white">{{ $trustedDevices->count() }}</dd>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-black/20 p-4">
                        <dt class="text-xs uppercase tracking-[0.25em] text-stone-500">Session state</dt>
                        <dd class="mt-2 text-lg font-semibold text-white">{{ $privilegedSessionActive ? 'Active' : 'Not active' }}</dd>
                    </div>
                </dl>
            </article>

            <article class="rounded-[2rem] border border-white/10 bg-stone-900/70 p-6 backdrop-blur">
                <h3 class="text-xl font-semibold text-white">Activate</h3>

                @if (! auth()->user()?->mfa_enabled)
                    <div class="mt-5 rounded-3xl border border-amber-300/20 bg-amber-400/10 p-4 text-sm text-amber-100">
                        MFA has not been enabled on this account yet, so a privileged web session cannot be activated.
                    </div>
                @elseif ($trustedDevices->isEmpty())
                    <div class="mt-5 rounded-3xl border border-amber-300/20 bg-amber-400/10 p-4 text-sm text-amber-100">
                        No active trusted device was found for this account. Enroll one through the API security flow first.
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.security.store') }}" class="mt-5 space-y-5">
                        @csrf

                        <div class="space-y-2">
                            <label for="trusted_device_id" class="text-sm font-medium text-stone-200">Trusted device</label>
                            <select id="trusted_device_id" name="trusted_device_id" class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white focus:border-emerald-300/60 focus:outline-none focus:ring-2 focus:ring-emerald-300/20">
                                <option value="">Choose a device</option>
                                @foreach ($trustedDevices as $device)
                                    <option value="{{ $device->id }}">{{ $device->device_name }} @if($device->last_used_at) · last used {{ $device->last_used_at->diffForHumans() }} @endif</option>
                                @endforeach
                            </select>
                        </div>

                        <label class="flex items-start gap-3 rounded-3xl border border-white/10 bg-black/20 p-4">
                            <input type="checkbox" name="mfa_verified" value="1" class="mt-1 h-4 w-4 rounded border-white/20 bg-transparent text-emerald-400 focus:ring-emerald-300/30">
                            <span class="text-sm leading-6 text-stone-200">I have completed the MFA step for this account and want to unlock privileged admin actions in this browser session.</span>
                        </label>

                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-400 px-4 py-3 text-sm font-semibold text-stone-950 transition hover:bg-emerald-300">
                                Activate privileged session
                            </button>
                        </div>
                    </form>
                @endif

                @if ($privilegedSessionActive)
                    <form method="POST" action="{{ route('admin.security.destroy') }}" class="mt-4">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl border border-white/10 px-4 py-3 text-sm font-semibold text-stone-200 transition hover:border-white/20 hover:bg-white/5 hover:text-white">
                            Clear session
                        </button>
                    </form>
                @endif
            </article>
        </section>
    </div>
</x-admin.layout>
