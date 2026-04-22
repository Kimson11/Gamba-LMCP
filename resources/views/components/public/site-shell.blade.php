@props([
    'title' => 'LMCP',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} · {{ config('app.name', 'Gamba') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-stone-100 text-stone-950 antialiased">
        <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.10),_transparent_25%),radial-gradient(circle_at_top_right,_rgba(245,158,11,0.10),_transparent_26%),linear-gradient(180deg,_#f6f8f4_0%,_#f5f5f4_44%,_#fafaf9_100%)]">
            <div class="mx-auto flex min-h-screen max-w-7xl flex-col px-4 py-6 sm:px-6 lg:px-8">
                <header class="mb-8 rounded-[2rem] border border-stone-200/80 bg-white/80 px-5 py-4 shadow-sm shadow-stone-900/5 backdrop-blur">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="space-y-1">
                            <a href="{{ route('landing') }}" class="inline-flex items-center gap-3">
                                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-stone-950 text-sm font-semibold tracking-[0.2em] text-white">LM</span>
                                <span>
                                    <span class="block text-xs font-semibold uppercase tracking-[0.35em] text-stone-500">LMCP</span>
                                    <span class="block text-lg font-semibold tracking-tight text-stone-950">Livestock Cooperative Platform</span>
                                </span>
                            </a>
                        </div>

                        <nav class="flex flex-wrap items-center gap-2 text-sm">
                            <a href="#role-paths" class="rounded-full px-4 py-2 text-stone-600 transition hover:bg-stone-950/5 hover:text-stone-950">Role paths</a>
                            <a href="{{ route('admin.login') }}" class="rounded-full px-4 py-2 text-stone-600 transition hover:bg-stone-950/5 hover:text-stone-950">Admin login</a>
                            <a href="{{ route('admin.login') }}" class="inline-flex items-center justify-center rounded-full bg-stone-950 px-4 py-2 font-semibold text-white transition hover:bg-stone-800">Open portal</a>
                        </nav>
                    </div>
                </header>

                <main class="flex-1 space-y-8">
                    {{ $slot }}
                </main>

                <footer class="mt-8 rounded-[2rem] border border-stone-200/80 bg-white/80 px-6 py-5 shadow-sm shadow-stone-900/5 backdrop-blur">
                    <div class="flex flex-col gap-3 text-sm text-stone-600 lg:flex-row lg:items-center lg:justify-between">
                        <p>LMCP supports trust-first cooperative operations across mobile field workflows and governed web administration.</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <a href="{{ route('landing') }}" class="transition hover:text-stone-950">Home</a>
                            <a href="{{ route('admin.login') }}" class="transition hover:text-stone-950">Admin Portal</a>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    </body>
</html>
