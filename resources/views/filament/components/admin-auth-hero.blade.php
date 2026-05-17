<div class="pk-admin-auth-hero">
    <div class="pk-admin-auth-hero__glow pk-admin-auth-hero__glow--emerald"></div>
    <div class="pk-admin-auth-hero__glow pk-admin-auth-hero__glow--sky"></div>
    <div class="pk-admin-auth-hero__pattern"></div>

    <div class="pk-admin-auth-hero__content">
        <a href="{{ url('/') }}" class="pk-admin-auth-hero__brand">
            <img
                src="{{ asset(config('branding.assets.logo_mark', config('branding.logo_mark'))) }}"
                alt="{{ config('branding.name', config('app.name')) }}"
                class="pk-admin-auth-hero__brand-image"
            >
        </a>

        <div class="pk-admin-auth-hero__copy">
            <h2 class="pk-admin-auth-hero__title">
                Performa tim Anda<br>dalam satu <span>pandangan.</span>
            </h2>
            <p class="pk-admin-auth-hero__description">
                Dari penetapan target hingga penilaian akhir — semua terdokumentasi, transparan, dan bisa ditindaklanjuti.
            </p>
        </div>

        <ul class="pk-admin-auth-hero__benefits">
            @foreach ([
                ['title' => 'Target yang dipahami semua orang',     'desc' => 'Tidak ada lagi kebingungan soal ekspektasi performa.'],
                ['title' => 'Penilaian yang adil dan terverifikasi', 'desc' => 'Proses review terdokumentasi, bukan sekadar obrolan.'],
                ['title' => 'Laporan siap tanpa repot',              'desc' => 'Tidak perlu kompilasi data dari berbagai spreadsheet.'],
            ] as $index => $benefit)
                <li class="pk-admin-auth-hero__benefit">
                    <div class="pk-admin-auth-hero__benefit-track">
                        <span class="pk-admin-auth-hero__benefit-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </span>
                        @if ($index < 2)
                            <span class="pk-admin-auth-hero__benefit-line"></span>
                        @endif
                    </div>
                    <div>
                        <p class="pk-admin-auth-hero__benefit-title">{{ $benefit['title'] }}</p>
                        <p class="pk-admin-auth-hero__benefit-description">{{ $benefit['desc'] }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="pk-admin-auth-hero__footer">
        <svg class="pk-admin-auth-hero__footer-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
        </svg>
        <p>&copy; {{ date('Y') }} {{ config('branding.name', config('app.name')) }}. Hak cipta dilindungi.</p>
    </div>
</div>
