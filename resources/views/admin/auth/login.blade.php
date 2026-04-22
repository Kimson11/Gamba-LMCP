<x-admin.layout title="Admin Sign In">
    <div class="mx-auto flex min-h-[70vh] max-w-6xl items-center">
        <div class="grid w-full gap-8 lg:grid-cols-[1.1fr_0.9fr]">
            <section class="relative overflow-hidden rounded-[2.5rem] border border-white/10 bg-white/5 p-8 shadow-2xl shadow-black/20 backdrop-blur sm:p-10">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(245,158,11,0.18),_transparent_35%),radial-gradient(circle_at_bottom_left,_rgba(16,185,129,0.18),_transparent_32%)]"></div>
                <div class="relative space-y-6">
                    <div class="space-y-3">
                        <p class="text-sm font-semibold uppercase tracking-[0.35em] text-amber-200/70">Field Governance</p>
                        <h2 class="max-w-xl text-4xl font-semibold tracking-tight text-white sm:text-5xl">Operational control built for hard days in the field.</h2>
                        <p class="max-w-2xl text-base leading-7 text-stone-300">
                            Review assignments, monitor production exceptions, and keep cooperative operations moving with the same server-authoritative controls the mobile clients rely on.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-3xl border border-white/10 bg-black/20 p-5">
                            <p class="text-xs uppercase tracking-[0.25em] text-stone-400">Governance</p>
                            <p class="mt-3 text-2xl font-semibold text-white">Append-only approvals</p>
                        </div>
                        <div class="rounded-3xl border border-white/10 bg-black/20 p-5">
                            <p class="text-xs uppercase tracking-[0.25em] text-stone-400">Security</p>
                            <p class="mt-3 text-2xl font-semibold text-white">Privileged session checks</p>
                        </div>
                        <div class="rounded-3xl border border-white/10 bg-black/20 p-5">
                            <p class="text-xs uppercase tracking-[0.25em] text-stone-400">Visibility</p>
                            <p class="mt-3 text-2xl font-semibold text-white">Scoped operations</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-[2.5rem] border border-white/10 bg-stone-900/80 p-8 shadow-2xl shadow-black/20 backdrop-blur sm:p-10">
                <div class="space-y-6">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-200/70">Admin Access</p>
                        <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white">Sign in to continue</h2>
                    </div>

                    <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-5">
                        @csrf

                        <div class="space-y-2">
                            <label for="email" class="text-sm font-medium text-stone-200">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white placeholder:text-stone-500 focus:border-emerald-300/60 focus:outline-none focus:ring-2 focus:ring-emerald-300/20" placeholder="admin@example.com">
                        </div>

                        <div class="space-y-2">
                            <label for="password" class="text-sm font-medium text-stone-200">Password</label>
                            <input id="password" name="password" type="password" class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white placeholder:text-stone-500 focus:border-emerald-300/60 focus:outline-none focus:ring-2 focus:ring-emerald-300/20" placeholder="Enter your password">
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-400 px-4 py-3 text-sm font-semibold text-stone-950 transition hover:bg-emerald-300">
                            Sign in
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</x-admin.layout>
