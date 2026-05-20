<x-filament-panels::page.simple>
    <div class="pk-admin-login-card">
        <div class="pk-admin-login-card__header pk-admin-login-card__header--centered">
            <a href="{{ url('/') }}" class="pk-admin-login-card__logo">
                <img
                    src="{{ asset(config('branding.assets.logo_full', config('branding.logo_full'))) }}"
                    alt="{{ config('branding.name', config('app.name')) }}"
                    class="pk-admin-login-card__logo-image"
                >
            </a>
        </div>

        <div class="pk-admin-login-card__intro pk-admin-login-card__intro--centered">
            <h1 class="pk-admin-login-card__title">Akses Admin PulseKPI</h1>
            <p class="pk-admin-login-card__description">
                Masuk untuk melanjutkan ke panel admin dan area operasional KPI.
            </p>
        </div>

        <div class="pk-admin-login-card__form">
            {{ $this->content }}
        </div>

        <p class="pk-admin-login-card__footnote pk-admin-login-card__footnote--centered">
            Developed by <span class="pk-admin-login-card__developer">Arva Digital Media</span>
        </p>
    </div>
</x-filament-panels::page.simple>
