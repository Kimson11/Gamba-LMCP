<x-public.site-shell title="LMCP">
    <section class="relative overflow-hidden rounded-[2.5rem] border border-emerald-200/20 bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.20),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(245,158,11,0.18),_transparent_32%),linear-gradient(135deg,_rgba(11,17,16,0.98),_rgba(17,24,39,0.94))] px-6 py-8 sm:px-8 lg:px-12 lg:py-12">
        <div class="absolute inset-y-0 right-0 hidden w-1/2 bg-[radial-gradient(circle_at_center,_rgba(255,255,255,0.08),_transparent_58%)] lg:block"></div>

        <div class="relative grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
            <div class="space-y-6">
                <div class="inline-flex w-fit items-center gap-2 rounded-full border border-white/10 bg-white/8 px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-emerald-100/85">
                    <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                    Livestock Cooperative Management Platform
                </div>

                <div class="space-y-4">
                    <h1 class="max-w-3xl text-4xl font-semibold tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Trust-first cooperative operations for dairy, services, marketplace, and finance.
                    </h1>
                    <p class="max-w-2xl text-base leading-8 text-stone-200/90 sm:text-lg">
                        LMCP helps cooperatives, members, processors, and field teams manage daily livestock operations with offline-first mobile workflows, auditable approvals, and server-authoritative settlement.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('admin.login') }}" class="inline-flex items-center justify-center rounded-full bg-emerald-300 px-6 py-3 text-sm font-semibold text-stone-950 transition hover:bg-emerald-200">
                        Open Admin Portal
                    </a>
                    <a href="#role-paths" class="inline-flex items-center justify-center rounded-full border border-white/15 bg-white/5 px-6 py-3 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/10">
                        Explore Role Paths
                    </a>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-3xl border border-white/10 bg-black/20 p-4">
                        <p class="text-xs uppercase tracking-[0.25em] text-stone-400">Offline-first</p>
                        <p class="mt-3 text-sm leading-6 text-stone-200">Android field use with queued sync and conflict review.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-black/20 p-4">
                        <p class="text-xs uppercase tracking-[0.25em] text-stone-400">Governed</p>
                        <p class="mt-3 text-sm leading-6 text-stone-200">Append-only approvals, scoped access, and review traces.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-black/20 p-4">
                        <p class="text-xs uppercase tracking-[0.25em] text-stone-400">Operational</p>
                        <p class="mt-3 text-sm leading-6 text-stone-200">Milk logging, assignments, notifications, and admin analytics.</p>
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="rounded-[2rem] border border-white/10 bg-white/8 p-5 shadow-2xl shadow-black/30 backdrop-blur">
                    <div class="grid gap-4">
                        <div class="rounded-[1.75rem] border border-cyan-200/20 bg-cyan-400/10 p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-100/80">Today Snapshot</p>
                                    <p class="mt-3 text-3xl font-semibold text-white">Field-ready</p>
                                </div>
                                <span class="rounded-full bg-cyan-300/20 px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] text-cyan-100">Live</span>
                            </div>
                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-3xl border border-white/10 bg-black/20 p-4">
                                    <p class="text-xs uppercase tracking-[0.25em] text-stone-400">Milk Logging</p>
                                    <p class="mt-2 text-sm text-white">Offline capture and later sync replay</p>
                                </div>
                                <div class="rounded-3xl border border-white/10 bg-black/20 p-4">
                                    <p class="text-xs uppercase tracking-[0.25em] text-stone-400">Admin Review</p>
                                    <p class="mt-2 text-sm text-white">Analytics, approvals, notifications, and history</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-[1.75rem] border border-amber-200/20 bg-amber-400/10 p-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-amber-100/80">Marketplace</p>
                                <p class="mt-3 text-lg font-semibold text-white">Browse before sign-in</p>
                                <p class="mt-2 text-sm leading-6 text-stone-200">Public trust-first discovery is supported, while transactions and settlement stay authenticated and governed.</p>
                            </div>
                            <div class="rounded-[1.75rem] border border-emerald-200/20 bg-emerald-400/10 p-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-emerald-100/80">Governance</p>
                                <p class="mt-3 text-lg font-semibold text-white">Audit and traceability</p>
                                <p class="mt-2 text-sm leading-6 text-stone-200">Approvals, decisions, and sensitive actions remain reviewable across cooperative workflows.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="role-paths" class="grid gap-6 lg:grid-cols-3">
        <article class="rounded-[2rem] border border-stone-200/15 bg-white p-6 shadow-sm shadow-stone-900/5">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-emerald-700">Members</p>
            <h2 class="mt-3 text-2xl font-semibold tracking-tight text-stone-950">Daily field work without connectivity anxiety</h2>
            <p class="mt-3 text-sm leading-7 text-stone-600">Log milk, track sync progress, receive operational notifications, and move through guided mobile flows designed for low-literacy and low-bandwidth conditions.</p>
        </article>

        <article class="rounded-[2rem] border border-stone-200/15 bg-white p-6 shadow-sm shadow-stone-900/5">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-cyan-700">Cooperatives</p>
            <h2 class="mt-3 text-2xl font-semibold tracking-tight text-stone-950">Scoped oversight with approvals, analytics, and history</h2>
            <p class="mt-3 text-sm leading-7 text-stone-600">Use the admin portal to manage members, review approvals, monitor milk totals, filter notifications, and maintain a defensible audit trail for operational decisions.</p>
        </article>

        <article class="rounded-[2rem] border border-stone-200/15 bg-white p-6 shadow-sm shadow-stone-900/5">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-amber-700">Processors & Partners</p>
            <h2 class="mt-3 text-2xl font-semibold tracking-tight text-stone-950">Vendor-facing marketplace visibility with controlled settlement</h2>
            <p class="mt-3 text-sm leading-7 text-stone-600">Public discovery supports adoption, but protected workflows keep cooperative governance, pricing control, and settlement integrity inside authenticated channels.</p>
        </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <article class="rounded-[2rem] border border-stone-200/15 bg-stone-950 p-8 text-stone-100">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-amber-200/80">How It Works</p>
            <div class="mt-6 space-y-5">
                <div class="flex gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-300 text-sm font-semibold text-stone-950">1</div>
                    <div>
                        <h3 class="text-lg font-semibold text-white">Capture operations where the work happens</h3>
                        <p class="mt-2 text-sm leading-6 text-stone-300">Members and field teams use mobile workflows for milk, services, and operational records even when connectivity is intermittent.</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-cyan-300 text-sm font-semibold text-stone-950">2</div>
                    <div>
                        <h3 class="text-lg font-semibold text-white">Sync into a server-authoritative backend</h3>
                        <p class="mt-2 text-sm leading-6 text-stone-300">Queued changes replay with idempotent handling, scoped access checks, and conflict review where the server detects competing or stale submissions.</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-300 text-sm font-semibold text-stone-950">3</div>
                    <div>
                        <h3 class="text-lg font-semibold text-white">Review, approve, and govern</h3>
                        <p class="mt-2 text-sm leading-6 text-stone-300">Cooperative admins use analytics, notification filters, and approval history to keep operations moving without sacrificing traceability.</p>
                    </div>
                </div>
            </div>
        </article>

        <article class="rounded-[2rem] border border-stone-200/15 bg-white p-8 shadow-sm shadow-stone-900/5">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-stone-500">Trust & Governance</p>
                    <h2 class="mt-3 text-3xl font-semibold tracking-tight text-stone-950">Built for regulated, multi-actor cooperative operations</h2>
                </div>
                <a href="{{ route('admin.login') }}" class="inline-flex items-center justify-center rounded-full border border-stone-300 px-5 py-3 text-sm font-semibold text-stone-800 transition hover:border-stone-950 hover:bg-stone-950 hover:text-white">
                    Sign in
                </a>
            </div>

            <div class="mt-8 grid gap-4 md:grid-cols-2">
                <div class="rounded-[1.75rem] border border-stone-200 bg-stone-50 p-5">
                    <h3 class="text-lg font-semibold text-stone-950">Separation of duties</h3>
                    <p class="mt-2 text-sm leading-6 text-stone-600">Approval-sensitive actions stay distinct from submission flows, reducing fraud and self-approval risk.</p>
                </div>
                <div class="rounded-[1.75rem] border border-stone-200 bg-stone-50 p-5">
                    <h3 class="text-lg font-semibold text-stone-950">Append-only review trails</h3>
                    <p class="mt-2 text-sm leading-6 text-stone-600">Approval history stays traceable so cooperatives can explain who changed what and when.</p>
                </div>
                <div class="rounded-[1.75rem] border border-stone-200 bg-stone-50 p-5">
                    <h3 class="text-lg font-semibold text-stone-950">Offline conflict handling</h3>
                    <p class="mt-2 text-sm leading-6 text-stone-600">Server-side reconciliation and reviewer notes help teams resolve replay conflicts without hiding data issues.</p>
                </div>
                <div class="rounded-[1.75rem] border border-stone-200 bg-stone-50 p-5">
                    <h3 class="text-lg font-semibold text-stone-950">Role-aware access</h3>
                    <p class="mt-2 text-sm leading-6 text-stone-600">Members, cooperatives, and privileged operators each enter the platform through the flows designed for their responsibilities.</p>
                </div>
            </div>
        </article>
    </section>

    <section class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
        <article class="rounded-[2rem] border border-stone-200/15 bg-white p-8 shadow-sm shadow-stone-900/5">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-stone-500">Marketplace Preview</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-stone-950">Public-safe discovery, private settlement</h2>
            <p class="mt-3 text-sm leading-7 text-stone-600">The public site can preview supply and marketplace intent to build trust and adoption, but final negotiation, approval, and settlement remain authenticated and cooperative-scoped.</p>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-[1.75rem] border border-stone-200 bg-stone-50 p-5">
                    <p class="text-xs uppercase tracking-[0.25em] text-stone-500">Public browse</p>
                    <p class="mt-2 text-base font-semibold text-stone-950">Listings and trust signals</p>
                </div>
                <div class="rounded-[1.75rem] border border-stone-200 bg-stone-50 p-5">
                    <p class="text-xs uppercase tracking-[0.25em] text-stone-500">Protected action</p>
                    <p class="mt-2 text-base font-semibold text-stone-950">Offers, approvals, and settlement</p>
                </div>
            </div>
        </article>

        <article class="rounded-[2rem] border border-stone-200/15 bg-[linear-gradient(135deg,_#fff7ed,_#f0fdf4)] p-8 shadow-sm shadow-stone-900/5">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-stone-500">Support</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-stone-950">Start from the right path</h2>
            <div class="mt-6 grid gap-4">
                <div class="rounded-[1.75rem] border border-stone-200/80 bg-white/80 p-5">
                    <h3 class="text-lg font-semibold text-stone-950">Cooperative staff and reviewers</h3>
                    <p class="mt-2 text-sm leading-6 text-stone-600">Use the admin portal for scoped operations, analytics, approvals, and notifications.</p>
                    <a href="{{ route('admin.login') }}" class="mt-4 inline-flex items-center justify-center rounded-full bg-stone-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-stone-800">Admin sign in</a>
                </div>
                <div class="rounded-[1.75rem] border border-stone-200/80 bg-white/80 p-5">
                    <h3 class="text-lg font-semibold text-stone-950">Members and field teams</h3>
                    <p class="mt-2 text-sm leading-6 text-stone-600">Mobile onboarding and role-aware entry points are designed for quick, guided operational use.</p>
                    <span class="mt-4 inline-flex items-center rounded-full bg-stone-900/5 px-4 py-2 text-sm font-medium text-stone-600">Mobile rollout path in progress</span>
                </div>
            </div>
        </article>
    </section>
</x-public.site-shell>
