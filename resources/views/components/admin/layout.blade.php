@props([
    'title' => 'Admin',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} · {{ config('app.name', 'Gamba') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-stone-950 text-stone-100 antialiased">
        <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(34,197,94,0.18),_transparent_32%),radial-gradient(circle_at_top_right,_rgba(245,158,11,0.14),_transparent_30%),linear-gradient(180deg,_#0c1210_0%,_#141c19_48%,_#0b1110_100%)]">
            <div class="mx-auto flex min-h-screen max-w-7xl flex-col px-4 py-6 sm:px-6 lg:px-8">
                <header class="mb-6 rounded-[2rem] border border-white/10 bg-white/5 px-5 py-4 shadow-2xl shadow-black/20 backdrop-blur">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-amber-200/70">LMCP Admin</p>
                            <h1 class="text-2xl font-semibold tracking-tight text-white">{{ $title }}</h1>
                        </div>

                        @auth
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <nav class="flex flex-wrap items-center gap-2 text-sm">
                                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'bg-emerald-400/20 text-emerald-100 ring-1 ring-emerald-300/30' : 'text-stone-300 hover:bg-white/5 hover:text-white' }} rounded-full px-4 py-2 transition">Dashboard</a>
                                    <a href="{{ route('admin.analytics.index') }}" class="{{ request()->routeIs('admin.analytics.*') ? 'bg-cyan-400/20 text-cyan-100 ring-1 ring-cyan-300/30' : 'text-stone-300 hover:bg-white/5 hover:text-white' }} rounded-full px-4 py-2 transition">Analytics</a>
                                    <a href="{{ route('admin.assignments.index') }}" class="{{ request()->routeIs('admin.assignments.*') ? 'bg-emerald-400/20 text-emerald-100 ring-1 ring-emerald-300/30' : 'text-stone-300 hover:bg-white/5 hover:text-white' }} rounded-full px-4 py-2 transition">Approvals</a>
                                    <a href="{{ route('admin.notifications.index') }}" class="{{ request()->routeIs('admin.notifications.*') ? 'bg-emerald-400/20 text-emerald-100 ring-1 ring-emerald-300/30' : 'text-stone-300 hover:bg-white/5 hover:text-white' }} rounded-full px-4 py-2 transition">Notifications</a>
                                    @if(auth()->user()?->isPrivileged())
                                        <a href="{{ route('admin.security.show') }}" class="{{ request()->routeIs('admin.security.*') ? 'bg-amber-400/20 text-amber-100 ring-1 ring-amber-300/30' : 'text-stone-300 hover:bg-white/5 hover:text-white' }} rounded-full px-4 py-2 transition">Security</a>
                                    @endif
                                </nav>

                                <form method="POST" action="{{ route('admin.logout') }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center justify-center rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-stone-200 transition hover:border-white/20 hover:bg-white/10 hover:text-white">
                                        Sign out
                                    </button>
                                </form>
                            </div>
                        @endauth
                    </div>
                </header>

                @if (session('status'))
                    <div class="mb-6 rounded-3xl border border-emerald-300/20 bg-emerald-400/10 px-5 py-4 text-sm text-emerald-100">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-3xl border border-rose-300/20 bg-rose-400/10 px-5 py-4 text-sm text-rose-100">
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <main class="flex-1">
                    {{ $slot }}
                </main>
            </div>
        </div>
        @livewireScripts
    </body>
</html>
