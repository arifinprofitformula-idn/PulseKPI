<x-filament-panels::page.simple>
    <div class="pk-admin-login-card">
        <div class="pk-admin-login-card__header">
            <a href="{{ url('/') }}" class="pk-admin-login-card__logo">
                <img
                    src="{{ asset(config('branding.assets.logo_full', config('branding.logo_full'))) }}"
                    alt="{{ config('branding.name', config('app.name')) }}"
                    class="pk-admin-login-card__logo-image"
                >
            </a>

            <span class="pk-admin-login-card__badge">
                <svg class="pk-admin-login-card__badge-icon" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                Secure Admin
            </span>
        </div>

        <div class="pk-admin-login-card__intro">
            <h1 class="pk-admin-login-card__title">Masuk ke Admin Panel</h1>
            <p class="pk-admin-login-card__description">
                Selamat datang kembali. Masuk untuk melanjutkan ke workspace Anda.
            </p>
        </div>

        <div class="pk-admin-login-card__form">
            {{ $this->content }}
        </div>

        <p class="pk-admin-login-card__footnote">
            Hanya akun dengan izin panel admin yang dapat melanjutkan ke area ini.
        </p>

    </div>
</x-filament-panels::page.simple>
