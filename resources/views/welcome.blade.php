<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta
            name="description"
            content="{{ config('branding.description', 'Platform manajemen performa untuk KPI, assessment, approval, dan pelaporan.') }}"
        >
        <title>{{ config('branding.name', config('app.name')) }} &mdash; {{ config('branding.tagline', 'Performance Management Platform') }}</title>
        <link rel="icon" type="image/png" href="{{ asset(config('branding.assets.favicon', 'favicon.png')) }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased" style="background-color: #f8fafc; color: #0f172a;">

        @php
            $painPoints = [
                [
                    'title' => 'Target kerja sering tersebar',
                    'description' => 'Template, assignment, dan indikator kinerja sering terpisah di chat, file, atau spreadsheet yang sulit ditelusuri ulang.',
                    'icon' => 'M9 12.75 11.25 15 15 9.75m6 2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9 12h.008v.008H9V12Zm3 0h.008v.008H12V12Zm3 0h.008v.008H15V12Z',
                    'accent' => 'amber',
                ],
                [
                    'title' => 'Proses review lambat dan tidak konsisten',
                    'description' => 'Status assessment mudah membingungkan ketika tiap atasan atau unit bekerja dengan format dan alur yang berbeda.',
                    'icon' => 'M12 6v6l4.5 4.5m6-4.5a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                    'accent' => 'blue',
                ],
                [
                    'title' => 'Laporan sulit disiapkan cepat',
                    'description' => 'HRD dan approver menghabiskan waktu merapikan data sebelum bisa mengambil keputusan atau membagikan hasil.',
                    'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m.75 12 3 3m0 0 3-3m-3 3V10.5m-7.5 6.75h12a2.25 2.25 0 0 0 2.25-2.25V9.75a2.25 2.25 0 0 0-2.25-2.25h-12A2.25 2.25 0 0 0 3 9.75V15a2.25 2.25 0 0 0 2.25 2.25Z',
                    'accent' => 'rose',
                ],
            ];

            $valueCards = [
                [
                    'title' => 'Satu sumber data KPI',
                    'description' => 'Semua pihak bekerja pada assignment, assessment, skor, dan status yang sama tanpa rekap manual berulang.',
                    'outcome' => 'Tidak ada lagi versi data yang berbeda antara karyawan, manager, dan HRD.',
                    'icon' => 'M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h12a2.25 2.25 0 0 0 2.25-2.25V3.75M3.75 3h16.5M9 8.25h6m-6 3h6m-6 3h3',
                    'color' => 'emerald',
                ],
                [
                    'title' => 'Workflow yang bisa dipertanggungjawabkan',
                    'description' => 'Draft, submitted, reviewed, approved, rejected, hingga locked mengikuti alur yang jelas dan mudah diaudit.',
                    'outcome' => 'Setiap perubahan status tercatat—siapa mengerjakan apa, dan kapan.',
                    'icon' => 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 0h10.5A2.25 2.25 0 0 1 18.75 12v6A2.25 2.25 0 0 1 16.5 20.25h-9A2.25 2.25 0 0 1 5.25 18v-6a2.25 2.25 0 0 1 2.25-2.25Z',
                    'color' => 'blue',
                ],
                [
                    'title' => 'Keputusan lebih cepat',
                    'description' => 'Dashboard, antrian review, distribusi nilai, dan ekspor laporan membantu tindakan lanjut tanpa menunggu kompilasi data.',
                    'outcome' => 'HRD dan pimpinan tidak perlu menunggu rekap manual sebelum bisa bertindak.',
                    'icon' => 'M3 13.5 7.5 9l4.5 4.5L21 4.5M18.75 4.5H21v2.25',
                    'color' => 'indigo',
                ],
                [
                    'title' => 'Siap dipakai lintas peran',
                    'description' => 'Karyawan, manager, HRD, dan approver masing-masing masuk ke workspace yang sesuai dengan tanggung jawabnya.',
                    'outcome' => 'Tidak ada akses berlebihan, tidak ada kebingungan tampilan yang bukan urusan mereka.',
                    'icon' => 'M18 18.72a8.966 8.966 0 0 1-6 2.03 8.966 8.966 0 0 1-6-2.03m12 0a9 9 0 1 0-12 0m12 0A9 9 0 0 0 12 3.75a9 9 0 0 0-6 14.97m12 0v-.75A2.25 2.25 0 0 0 15.75 15h-7.5A2.25 2.25 0 0 0 6 17.25v.72m9-10.47a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
                    'color' => 'teal',
                ],
            ];

            $steps = [
                [
                    'number' => '01',
                    'title' => 'Template & assignment ditetapkan',
                    'description' => 'HRD membuat template KPI dan menetapkan assignment ke karyawan sesuai periode dan jabatan. Semua indikator, bobot, dan ekspektasi tercatat sejak awal—tidak ada yang perlu ditebak.',
                ],
                [
                    'number' => '02',
                    'title' => 'Karyawan mengisi, manager menilai',
                    'description' => 'Karyawan mengisi assessment dan mengunggah bukti pendukung. Manager mereview, memberikan nilai, dan meneruskan ke approver—semua dalam satu antrian kerja yang jelas tanpa koordinasi via chat.',
                ],
                [
                    'number' => '03',
                    'title' => 'Keputusan final & laporan siap ekspor',
                    'description' => 'Approver memberikan keputusan akhir. Setelah locked, laporan langsung bisa diekspor tanpa rekap manual. Semua pihak melihat hasil dari satu sumber yang sama.',
                ],
            ];

            $roleValues = [
                [
                    'role' => 'Karyawan',
                    'value' => 'Mengisi KPI dan bukti pendukung dengan lebih jelas, tanpa menebak target atau status penilaian.',
                    'benefits' => [
                        'Melihat assignment KPI dan indikator yang sudah ditetapkan untuk Anda',
                        'Mengisi assessment dan mengunggah bukti pendukung langsung di platform',
                        'Memantau status penilaian secara real-time—dari draft hingga approved',
                    ],
                    'icon' => 'M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z',
                    'color' => 'blue',
                ],
                [
                    'role' => 'Manager',
                    'value' => 'Memantau tim, menilai lebih tertib, dan segera melihat assessment mana yang perlu direvisi atau sudah keluar dari tangan Anda.',
                    'benefits' => [
                        'Antrian review yang jelas—hanya melihat assessment yang memang giliran Anda',
                        'Memberikan nilai dan catatan langsung tanpa koordinasi manual via chat',
                        'Dashboard tim untuk memantau progres assessment seluruh anggota sekaligus',
                    ],
                    'icon' => 'M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.814L12.75 3 2.25 18Z',
                    'color' => 'indigo',
                ],
                [
                    'role' => 'HRD',
                    'value' => 'Mengendalikan siklus KPI, menjaga kepatuhan proses, dan melihat bottleneck review dari satu panel operasional.',
                    'benefits' => [
                        'Kontrol penuh atas template, periode, dan assignment KPI lintas divisi',
                        'Visibilitas bottleneck—mana assessment yang macet dan di tahap siapa',
                        'Ekspor laporan distribusi nilai dan rekap performa tanpa rekap manual',
                    ],
                    'icon' => 'M9 12.75 11.25 15 15 9.75m6 2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                    'color' => 'emerald',
                ],
                [
                    'role' => 'Approver',
                    'value' => 'Fokus pada assessment yang siap diputuskan tanpa terganggu tahap workflow yang bukan cakupan Anda.',
                    'benefits' => [
                        'Hanya melihat assessment yang sudah siap untuk keputusan final Anda',
                        'Approve, reject, atau minta revisi dengan satu klik disertai catatan',
                        'Riwayat keputusan tersimpan otomatis untuk keperluan audit',
                    ],
                    'icon' => 'M9 12.75 11.25 15 15 9.75M12 3c-1.518 1.747-3.822 2.715-6.25 2.715-.38 0-.752-.024-1.115-.07A11.966 11.966 0 0 0 3.75 9.75c0 5.056 3.436 9.497 8.25 10.713 4.814-1.216 8.25-5.657 8.25-10.713 0-1.425-.248-2.792-.705-4.055-.363.046-.735.07-1.115.07-2.428 0-4.732-.968-6.25-2.715Z',
                    'color' => 'teal',
                ],
            ];

            $differentiators = [
                [
                    'text' => 'Berbasis workflow nyata, bukan sekadar form input KPI.',
                    'detail' => 'Setiap assignment punya status, setiap status punya aksi yang jelas, dan setiap transisi tercatat secara otomatis.',
                    'icon' => 'M10.5 6h9.75M10.5 12h9.75M10.5 18h9.75M3.75 6h.008v.008H3.75V6Zm0 6h.008v.008H3.75V12Zm0 6h.008v.008H3.75V18Z',
                ],
                [
                    'text' => 'Menyatukan assignment, assessment, approval, dan export dalam satu alur.',
                    'detail' => 'Tidak ada lagi lompatan antar tool—dari penetapan target sampai laporan akhir ada di satu platform yang sama.',
                    'icon' => 'M7.5 8.25h9m-9 3h5.25m-5.25 3h9M3.75 5.25A2.25 2.25 0 0 1 6 3h12a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 18 21H6a2.25 2.25 0 0 1-2.25-2.25V5.25Z',
                ],
                [
                    'text' => 'Status dan insight yang langsung terbaca tanpa menunggu laporan.',
                    'detail' => 'Dashboard real-time untuk semua peran—tidak ada yang perlu menunggu email atau update manual dari orang lain.',
                    'icon' => 'M3 3v1.5M3 19.5V21m18-18v1.5m0 15V21M8.25 7.5h7.5m-7.5 4.5h7.5m-9 9h10.5A2.25 2.25 0 0 0 19.5 18.75V6.75A2.25 2.25 0 0 0 17.25 4.5H6.75A2.25 2.25 0 0 0 4.5 6.75v12A2.25 2.25 0 0 0 6.75 21Z',
                ],
            ];
        @endphp

        {{-- ── STICKY NAV ──────────────────────────────────────────────────── --}}
        <nav class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/90 shadow-sm shadow-slate-900/5 backdrop-blur-md">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-5 py-3 sm:px-6">
                <a href="{{ url('/') }}" class="flex items-center gap-2.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:ring-offset-2">
                    <img
                        src="{{ asset(config('branding.assets.logo_full', config('branding.logo_full'))) }}"
                        alt="{{ config('branding.name', config('app.name')) }}"
                        class="h-9 w-9 object-contain"
                    >
                    <span class="text-base font-bold tracking-tight text-slate-950">{{ config('branding.name', config('app.name')) }}</span>
                </a>
                @auth
                    <a href="{{ route('dashboard') }}" class="pk-btn-primary px-4 py-2 text-sm">Buka Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="pk-btn-primary px-4 py-2 text-sm">Masuk Sekarang</a>
                @endauth
            </div>
        </nav>

        <main>

            {{-- ── 1. HERO ──────────────────────────────────────────────────── --}}
            <section class="pulse-fade-up pulse-delay-1 relative overflow-hidden px-5 py-20 text-white sm:px-6 sm:py-28" style="background: var(--brand-gradient-hero);">
                <div class="pointer-events-none absolute inset-0" aria-hidden="true" style="background-image: linear-gradient(rgb(148 163 184 / 0.08) 1px, transparent 1px), linear-gradient(90deg, rgb(148 163 184 / 0.08) 1px, transparent 1px); background-size: 32px 32px; mask-image: linear-gradient(180deg, rgb(0 0 0 / 0.45), transparent 90%);"></div>
                <div class="pointer-events-none absolute -right-24 -top-24 h-80 w-80 rounded-full opacity-20" style="background: radial-gradient(circle, #fff, transparent 70%); filter: blur(32px);" aria-hidden="true"></div>

                <div class="relative z-10 mx-auto max-w-3xl space-y-7 text-center">
                    <span class="pulse-chip mx-auto">Platform KPI internal — transparan, terstruktur, dan siap lintas peran</span>

                    <h1 class="text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Ubah proses KPI yang tersebar menjadi sistem performa yang tertib dan meyakinkan.
                    </h1>

                    <p class="mx-auto max-w-2xl text-base leading-8 text-cyan-50/90 sm:text-lg">
                        {{ config('branding.name', config('app.name')) }} membantu karyawan, manager, HRD, dan approver
                        menyatukan target, assessment, review, approval, dan pelaporan dalam satu workflow yang jelas—tanpa spreadsheet terpisah dan follow-up manual.
                    </p>

                    <div class="flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-xl bg-white px-7 py-3.5 text-sm font-semibold text-slate-900 shadow-lg shadow-black/20 transition hover:bg-cyan-50">
                                Buka Dashboard
                            </a>
                            <a href="{{ url('/admin') }}" class="inline-flex items-center rounded-xl border border-white/30 px-7 py-3.5 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/10">
                                Panel Admin
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center rounded-xl bg-white px-7 py-3.5 text-sm font-semibold text-slate-900 shadow-lg shadow-black/20 transition hover:bg-cyan-50">
                                Masuk Sekarang
                            </a>
                            <a href="{{ url('/admin') }}" class="inline-flex items-center rounded-xl border border-white/30 px-7 py-3.5 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/10">
                                Panel Admin
                            </a>
                        @endauth
                    </div>

                    <div class="mx-auto mt-4 flex max-w-xl flex-col items-center gap-6 border-t border-white/20 pt-8 sm:flex-row sm:justify-center sm:divide-x sm:divide-white/20">
                        <div class="px-6 text-center">
                            <p class="text-2xl font-bold text-white">4 Peran</p>
                            <p class="mt-0.5 text-sm text-cyan-100/70">dalam satu sistem</p>
                        </div>
                        <div class="px-6 text-center">
                            <p class="text-2xl font-bold text-white">1 Workflow</p>
                            <p class="mt-0.5 text-sm text-cyan-100/70">dari target ke laporan</p>
                        </div>
                        <div class="px-6 text-center">
                            <p class="text-2xl font-bold text-white">0 Rekap</p>
                            <p class="mt-0.5 text-sm text-cyan-100/70">manual berulang</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ── 2. PROBLEM ───────────────────────────────────────────────── --}}
            <section class="bg-white px-5 py-16 sm:px-6 sm:py-20">
                <div class="pulse-fade-up pulse-delay-2 mx-auto max-w-2xl space-y-4 text-center">
                    <p class="pulse-section-title">Masalah yang Diselesaikan</p>
                    <h2 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                        Apakah proses KPI di organisasi Anda terasa seperti ini?
                    </h2>
                    <p class="pulse-copy">
                        Banyak tim masih menjalankan KPI dengan cara yang melelahkan—tersebar, tidak konsisten, dan sulit dipertanggungjawabkan.
                    </p>
                </div>

                <div class="pulse-fade-up pulse-delay-3 mx-auto mt-10 max-w-3xl space-y-4">
                    @foreach ($painPoints as $point)
                        <article class="pulse-hover-lift flex items-start gap-4 rounded-2xl border p-5
                            {{ $point['accent'] === 'amber' ? 'border-amber-100 bg-amber-50/60' : ($point['accent'] === 'blue' ? 'border-blue-100 bg-blue-50/60' : 'border-rose-100 bg-rose-50/60') }}">
                            <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl
                                {{ $point['accent'] === 'amber' ? 'bg-amber-100 text-amber-700' : ($point['accent'] === 'blue' ? 'bg-blue-100 text-blue-700' : 'bg-rose-100 text-rose-700') }}">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $point['icon'] }}" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-base font-semibold text-slate-950">{{ $point['title'] }}</p>
                                <p class="mt-1.5 text-sm leading-7 text-slate-600">{{ $point['description'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            {{-- ── 3. SOLUTION BRIDGE ───────────────────────────────────────── --}}
            <div class="bg-slate-950 px-5 py-14 sm:px-6">
                <div class="pulse-fade-up mx-auto max-w-3xl space-y-3 text-center">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-400">Solusinya</p>
                    <p class="text-2xl font-bold leading-snug text-white sm:text-3xl lg:text-4xl">
                        Itulah mengapa {{ config('branding.name', config('app.name')) }} dibangun—<br class="hidden sm:block">
                        satu platform yang merapikan seluruh alur kerja KPI dari awal sampai laporan final.
                    </p>
                </div>
            </div>

            {{-- ── 4. VALUE PROPOSITIONS ────────────────────────────────────── --}}
            <section class="bg-slate-50 px-5 py-16 sm:px-6 sm:py-20">
                <div class="pulse-fade-up mx-auto max-w-2xl space-y-4 text-center">
                    <p class="pulse-section-title">Nilai Utama</p>
                    <h2 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                        Apa yang Anda dapatkan dari platform ini?
                    </h2>
                </div>

                <div class="pulse-fade-up pulse-delay-2 mx-auto mt-10 max-w-3xl space-y-4">
                    @foreach ($valueCards as $index => $card)
                        <article class="pulse-hover-lift flex items-start gap-5 rounded-2xl border p-6
                            {{ $index % 2 === 0 ? 'border-slate-200 bg-white shadow-sm shadow-slate-900/[0.04]' : 'border-slate-100 bg-slate-50' }}">
                            <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl
                                {{ $card['color'] === 'emerald' ? 'bg-emerald-100 text-emerald-700' : ($card['color'] === 'blue' ? 'bg-blue-100 text-blue-700' : ($card['color'] === 'indigo' ? 'bg-indigo-100 text-indigo-700' : 'bg-teal-100 text-teal-700')) }}">
                                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" />
                                </svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-lg font-bold text-slate-950">{{ $card['title'] }}</p>
                                <p class="mt-2 text-sm leading-7 text-slate-600">{{ $card['description'] }}</p>
                                <p class="mt-3 text-sm font-semibold text-emerald-700">&rarr; {{ $card['outcome'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            {{-- ── 5. HOW IT WORKS ──────────────────────────────────────────── --}}
            <section class="bg-white px-5 py-16 sm:px-6 sm:py-20">
                <div class="pulse-fade-up mx-auto max-w-2xl space-y-4 text-center">
                    <p class="pulse-section-title">Cara Kerjanya</p>
                    <h2 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                        Tiga langkah dari penetapan target ke laporan final.
                    </h2>
                    <p class="pulse-copy">Dirancang agar setiap peran tahu tugasnya dan setiap tahap bergerak maju tanpa hambatan.</p>
                </div>

                <div class="pulse-fade-up pulse-delay-2 mx-auto mt-12 max-w-2xl">
                    <div class="relative">
                        <div class="absolute left-7 top-14 h-[calc(100%-7rem)] w-px bg-slate-200 sm:left-9" aria-hidden="true"></div>
                        <div class="space-y-8">
                            @foreach ($steps as $step)
                                <div class="relative flex gap-5">
                                    <div class="relative z-10 flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-slate-950 text-white shadow-lg shadow-slate-950/25 sm:h-[4.5rem] sm:w-[4.5rem]">
                                        <span class="text-lg font-bold sm:text-xl">{{ $step['number'] }}</span>
                                    </div>
                                    <div class="flex-1 pt-2 sm:pt-3">
                                        <p class="text-lg font-bold text-slate-950">{{ $step['title'] }}</p>
                                        <p class="mt-2 text-sm leading-7 text-slate-600">{{ $step['description'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-10 text-center">
                        @auth
                            <a href="{{ route('dashboard') }}" class="pk-btn-primary inline-flex px-6 py-3 text-sm">
                                Mulai gunakan sekarang &rarr;
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="pk-btn-primary inline-flex px-6 py-3 text-sm">
                                Masuk dan coba sekarang &rarr;
                            </a>
                        @endauth
                    </div>
                </div>
            </section>

            {{-- ── 6. ROLE BENEFITS ─────────────────────────────────────────── --}}
            <section class="bg-slate-50 px-5 py-16 sm:px-6 sm:py-20">
                <div class="pulse-fade-up mx-auto max-w-2xl space-y-4 text-center">
                    <p class="pulse-section-title">Untuk Setiap Peran</p>
                    <h2 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                        Manfaat yang langsung Anda rasakan, sesuai peran Anda.
                    </h2>
                    <p class="pulse-copy">Pilih peran Anda untuk melihat apa yang paling relevan.</p>
                </div>

                <div class="pulse-fade-up pulse-delay-2 mx-auto mt-8 max-w-3xl">
                    <div class="flex flex-wrap justify-center gap-1.5 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-sm">
                        @foreach ($roleValues as $i => $item)
                            <button
                                type="button"
                                role="tab"
                                data-tab="role-{{ $i }}"
                                aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                                class="role-tab-btn min-w-[6rem] flex-1 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all {{ $i === 0 ? 'bg-slate-950 text-white shadow-md' : 'text-slate-500 hover:text-slate-900' }}"
                            >
                                {{ $item['role'] }}
                            </button>
                        @endforeach
                    </div>

                    @foreach ($roleValues as $i => $item)
                        <div
                            id="role-{{ $i }}"
                            role="tabpanel"
                            class="role-tab-panel mt-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/[0.04] {{ $i !== 0 ? 'hidden' : '' }}"
                        >
                            <div class="flex items-start gap-4">
                                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl
                                    {{ $item['color'] === 'blue' ? 'bg-blue-100 text-blue-700' : ($item['color'] === 'indigo' ? 'bg-indigo-100 text-indigo-700' : ($item['color'] === 'emerald' ? 'bg-emerald-100 text-emerald-700' : 'bg-teal-100 text-teal-700')) }}">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-lg font-bold text-slate-950">{{ $item['role'] }}</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ $item['value'] }}</p>
                                </div>
                            </div>
                            <ul class="mt-5 space-y-3">
                                @foreach ($item['benefits'] as $benefit)
                                    <li class="flex items-start gap-3">
                                        <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                        </span>
                                        <span class="text-sm leading-6 text-slate-700">{{ $benefit }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ── 7. DIFFERENTIATORS ───────────────────────────────────────── --}}
            <section class="px-5 py-16 sm:px-6 sm:py-20" style="background-color: #064e3b;">
                <div class="pulse-fade-up mx-auto max-w-2xl space-y-4 text-center">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-300">Pembeda Utama</p>
                    <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                        Bukan sekadar tempat input KPI.
                    </h2>
                    <p class="text-base leading-7 text-emerald-100/70">
                        PulseKPI diposisikan sebagai alat untuk membuat proses performa lebih rapi, cepat, dan kredibel.
                    </p>
                </div>

                <div class="pulse-fade-up pulse-delay-2 mx-auto mt-10 max-w-3xl space-y-4">
                    @foreach ($differentiators as $point)
                        <div class="pulse-hover-lift flex items-start gap-4 rounded-2xl border border-emerald-700/50 bg-emerald-900/40 p-5">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-500 text-white">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $point['icon'] }}" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-base font-semibold text-white">{{ $point['text'] }}</p>
                                <p class="mt-1.5 text-sm leading-6 text-emerald-100/70">{{ $point['detail'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ── 8. FINAL CTA ─────────────────────────────────────────────── --}}
            <section class="bg-slate-950 px-5 py-20 sm:px-6 sm:py-28">
                <div class="pulse-fade-up mx-auto max-w-2xl space-y-6 text-center">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-400">Mulai Sekarang</p>
                    <h2 class="text-3xl font-bold leading-tight text-white sm:text-4xl lg:text-5xl">
                        Dari penetapan target sampai laporan akhir, semua bergerak dalam satu sistem yang lebih mudah dipercaya.
                    </h2>
                    <p class="text-base leading-7 text-slate-400">
                        Lebih sedikit kebingungan, lebih sedikit pekerjaan manual, dan lebih banyak keputusan yang bisa diambil tepat waktu.
                    </p>
                    <div class="flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-xl bg-white px-7 py-3.5 text-sm font-semibold text-slate-950 shadow-lg transition hover:bg-cyan-50">
                                Buka Dashboard Sekarang
                            </a>
                            <a href="{{ url('/admin') }}" class="inline-flex items-center rounded-xl border border-white/15 px-7 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10">
                                Masuk ke Panel Admin
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center rounded-xl bg-white px-7 py-3.5 text-sm font-semibold text-slate-950 shadow-lg transition hover:bg-cyan-50">
                                Masuk dan Mulai Gunakan
                            </a>
                            <a href="{{ url('/admin') }}" class="inline-flex items-center rounded-xl border border-white/15 px-7 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10">
                                Akses Panel Admin
                            </a>
                        @endauth
                    </div>
                    <p class="text-xs text-slate-600">Sudah memiliki akun? Gunakan tombol masuk di atas.</p>
                </div>
            </section>

        </main>

        {{-- ── FOOTER ────────────────────────────────────────────────────────── --}}
        <footer class="border-t border-slate-800 bg-slate-950 px-5 py-6 sm:px-6">
            <div class="mx-auto flex max-w-5xl flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-500">&copy; {{ date('Y') }} {{ config('branding.name', config('app.name')) }}. Hak cipta dilindungi.</p>
                <p class="text-sm text-slate-500">
                    Dikembangkan oleh <span class="font-semibold text-slate-400">Arva Digital Media</span>
                </p>
            </div>
        </footer>

        <script>
            (function () {
                var btns = document.querySelectorAll('.role-tab-btn');
                var panels = document.querySelectorAll('.role-tab-panel');

                btns.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var target = btn.dataset.tab;

                        btns.forEach(function (b) {
                            b.classList.remove('bg-slate-950', 'text-white', 'shadow-md');
                            b.classList.add('text-slate-500');
                            b.setAttribute('aria-selected', 'false');
                        });

                        btn.classList.add('bg-slate-950', 'text-white', 'shadow-md');
                        btn.classList.remove('text-slate-500');
                        btn.setAttribute('aria-selected', 'true');

                        panels.forEach(function (p) { p.classList.add('hidden'); });

                        var active = document.getElementById(target);
                        if (active) active.classList.remove('hidden');
                    });
                });
            })();
        </script>

    </body>
</html>
