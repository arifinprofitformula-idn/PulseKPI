<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('branding.name', config('app.name')) }} &mdash; {{ config('branding.tagline') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex">

            {{-- ── Brand panel (hidden on mobile) ─────────────────────── --}}
            <div class="hidden lg:flex lg:w-1/2 xl:w-3/5 flex-col justify-between
                        bg-gradient-to-br from-brand-500 via-brand-600 to-navy-700 p-12">

                {{-- Logo + wordmark --}}
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-white font-bold text-lg leading-none">{{ config('branding.name', 'PulseKPI') }}</p>
                        <p class="text-white/70 text-xs">{{ config('branding.tagline') }}</p>
                    </div>
                </div>

                {{-- Headline --}}
                <div class="space-y-6">
                    <h1 class="text-4xl xl:text-5xl font-bold text-white leading-tight">
                        Pantau KPI<br>secara real-time.
                    </h1>
                    <p class="text-white/80 text-lg max-w-md">
                        {{ config('branding.description') }}
                    </p>

                    {{-- Feature pills --}}
                    <div class="flex flex-wrap gap-3 pt-2">
                        @foreach(['Template KPI', 'Assessment', 'Approval', 'Laporan', 'Export'] as $feat)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 text-white text-sm font-medium">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                {{ $feat }}
                            </span>
                        @endforeach
                    </div>
                </div>

                {{-- Footer note --}}
                <p class="text-white/50 text-sm">
                    &copy; {{ date('Y') }} {{ config('branding.name', 'PulseKPI') }}. All rights reserved.
                </p>
            </div>

            {{-- ── Form panel ──────────────────────────────────────────── --}}
            <div class="w-full lg:w-1/2 xl:w-2/5 flex flex-col justify-center
                        bg-white dark:bg-gray-900 px-6 sm:px-12 py-12">

                {{-- Mobile logo --}}
                <div class="flex items-center gap-2 mb-8 lg:hidden">
                    <div class="w-8 h-8 rounded-lg bg-brand-500 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <span class="font-bold text-gray-900 dark:text-white">{{ config('branding.name', 'PulseKPI') }}</span>
                </div>

                <div class="w-full max-w-sm mx-auto">
                    {{ $slot }}
                </div>
            </div>

        </div>
    </body>
</html>
