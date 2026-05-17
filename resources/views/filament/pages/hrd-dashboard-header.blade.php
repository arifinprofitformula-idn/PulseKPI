<div class="pk-hrd-hero">
    <div class="pk-hrd-hero__content">
        <div class="pk-hrd-hero__eyebrow">PulseKPI HRD Workspace</div>
        <h2 class="pk-hrd-hero__title">Selamat datang kembali, {{ $user->name }}.</h2>
        <p class="pk-hrd-hero__description">
            Pantau siklus KPI dari template, assignment, review, approval, hingga export dalam satu dashboard yang ringkas dan mudah ditindaklanjuti.
        </p>

        @if ($quickActions !== [])
            <div class="pk-hrd-hero__actions">
                @foreach ($quickActions as $action)
                    <a
                        href="{{ $action['url'] }}"
                        class="pk-hrd-hero__action pk-hrd-hero__action--{{ $action['style'] }}"
                    >
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        @else
            <div class="pk-hrd-hero__hint">
                Semua quick action mengikuti izin akses akun Anda. Saat ini tidak ada aksi operasional langsung yang tersedia.
            </div>
        @endif
    </div>

    <div class="pk-hrd-hero__panel">
        <div class="pk-hrd-hero__metric-label">Fokus hari ini</div>
        <div class="pk-hrd-hero__metric-value">Review, monitor, dan jaga alur KPI tetap bergerak.</div>
        <div class="pk-hrd-hero__chips">
            <span class="pk-hrd-chip pk-hrd-chip--emerald">Operasional KPI</span>
            <span class="pk-hrd-chip pk-hrd-chip--navy">Role-aware access</span>
            <span class="pk-hrd-chip pk-hrd-chip--slate">Private exports tetap aman</span>
        </div>
    </div>
</div>
