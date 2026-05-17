<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-stone-950 text-stone-100">
        <main class="mx-auto flex min-h-screen max-w-6xl flex-col justify-center gap-8 px-6 py-16 lg:px-8">
            <div class="max-w-3xl space-y-6">
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-amber-400">Phase 0 Foundation</p>
                <h1 class="text-4xl font-semibold tracking-tight text-white sm:text-6xl">
                    PulseKPI is ready for authentication, admin access control, and seeded platform roles.
                </h1>
                <p class="text-lg leading-8 text-stone-300">
                    This Laravel 12 foundation includes Breeze authentication, a Filament admin panel, Spatie permissions,
                    quality tooling, and CI wiring for the KPI roadmap.
                </p>
            </div>

            <div class="flex flex-wrap gap-4">
                @auth
                    <a
                        href="{{ url('/admin') }}"
                        class="rounded-full bg-amber-400 px-6 py-3 font-medium text-stone-950 transition hover:bg-amber-300"
                    >
                        Open Admin Panel
                    </a>
                    <a
                        href="{{ route('dashboard') }}"
                        class="rounded-full border border-stone-700 px-6 py-3 font-medium text-stone-100 transition hover:border-stone-500"
                    >
                        Open User Dashboard
                    </a>
                @else
                    <a
                        href="{{ route('login') }}"
                        class="rounded-full bg-amber-400 px-6 py-3 font-medium text-stone-950 transition hover:bg-amber-300"
                    >
                        Sign In
                    </a>
                    <a
                        href="{{ url('/admin') }}"
                        class="rounded-full border border-stone-700 px-6 py-3 font-medium text-stone-100 transition hover:border-stone-500"
                    >
                        Admin Panel
                    </a>
                @endauth
            </div>
        </main>
    </body>
</html>
