<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ config('branding.description') }}">

        <title>{{ config('branding.name', config('app.name')) }} &mdash; {{ config('branding.tagline') }}</title>
        <link rel="icon" type="image/png" href="{{ asset(config('branding.assets.favicon', 'favicon.png')) }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="flex min-h-screen">

            {{-- ── Left hero panel (tablet + desktop only) ── --}}
            <div class="relative hidden overflow-hidden md:flex md:w-[46%] md:flex-col lg:w-[48%]"
                 style="background: linear-gradient(160deg, #071220 0%, #0a1f35 45%, #081828 100%);">

                {{-- Glow blobs --}}
                <div class="pointer-events-none absolute inset-0 overflow-hidden">
                    <div class="absolute -right-16 -top-16 h-72 w-72 rounded-full opacity-40"
                         style="background: radial-gradient(circle, #0f9d8a 0%, transparent 70%); filter: blur(48px);"></div>
                    <div class="absolute left-1/3 top-1/3 h-56 w-56 rounded-full opacity-20"
                         style="background: radial-gradient(circle, #0ea5e9 0%, transparent 70%); filter: blur(40px);"></div>
                    <div class="absolute bottom-0 right-0 h-64 w-64 opacity-[0.07]"
                         style="background-image: radial-gradient(circle, #ffffff 1px, transparent 1px); background-size: 18px 18px;"></div>
                </div>

                {{-- Vertically centered content --}}
                <div class="relative z-10 flex flex-1 flex-col justify-center gap-9 px-8 py-12 lg:px-12 lg:py-16 xl:px-14">

                    {{-- Logo (logo-mark has dark bg, blends perfectly on dark panel) --}}
                    <a href="{{ url('/') }}" class="self-start rounded-2xl focus:outline-none focus:ring-2 focus:ring-white/30">
                        <img
                            src="{{ asset(config('branding.assets.logo_mark', config('branding.logo_mark'))) }}"
                            alt="{{ config('branding.name', config('app.name')) }}"
                            class="h-14 w-auto object-contain"
                        >
                    </a>

                    {{-- Headline --}}
                    <div class="space-y-4">
                        <h1 class="text-3xl font-bold leading-snug text-white lg:text-4xl xl:text-[2.65rem]">
                            Performa tim Anda<br>dalam satu <span style="color: #2dd4bf;">pandangan.</span>
                        </h1>
                        <p class="max-w-[18rem] text-sm leading-7 text-white/60 lg:text-[0.9375rem]">
                            Dari penetapan target hingga penilaian akhir — semua terdokumentasi, transparan, dan bisa ditindaklanjuti.
                        </p>
                    </div>

                    {{-- Benefit list --}}
                    <ul class="space-y-0">
                        @foreach ([
                            ['title' => 'Target yang dipahami semua orang',     'desc' => 'Tidak ada lagi kebingungan soal ekspektasi performa.'],
                            ['title' => 'Penilaian yang adil dan terverifikasi', 'desc' => 'Proses review terdokumentasi, bukan sekadar obrolan.'],
                            ['title' => 'Laporan siap tanpa repot',              'desc' => 'Tidak perlu kompilasi data dari berbagai spreadsheet.'],
                        ] as $i => $benefit)
                            <li class="flex gap-4 {{ $i < 2 ? 'pb-5' : '' }}">
                                <div class="flex flex-col items-center">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-500 shadow-md shadow-brand-500/30">
                                        <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                    </span>
                                    @if ($i < 2)
                                        <div class="mt-1 w-px flex-1 border-l border-dashed border-white/15"></div>
                                    @endif
                                </div>
                                <div class="pb-1">
                                    <p class="text-sm font-semibold text-white">{{ $benefit['title'] }}</p>
                                    <p class="mt-0.5 text-xs leading-5 text-white/50">{{ $benefit['desc'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Footer pinned to bottom --}}
                <div class="relative z-10 flex items-center gap-2 px-8 py-5 lg:px-12 xl:px-14">
                    <svg class="h-3.5 w-3.5 shrink-0 text-white/30" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                    <p class="text-xs text-white/30">&copy; {{ date('Y') }} {{ config('branding.name', config('app.name')) }}. Hak cipta dilindungi.</p>
                </div>
            </div>

            {{-- ── Right form panel ── --}}
            <div class="flex flex-1 flex-col items-center justify-center bg-slate-100 px-5 py-10 sm:px-8">
                <div class="w-full max-w-md">
                    {{ $slot }}
                </div>
            </div>

        </div>
    </body>
</html>
