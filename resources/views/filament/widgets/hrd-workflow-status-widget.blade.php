<section class="pk-hrd-card">
    <header class="pk-hrd-card__header">
        <div>
            <h3 class="pk-hrd-card__title">Workflow status</h3>
            <p class="pk-hrd-card__description">Ringkasan status utama untuk memantau pergerakan KPI.</p>
        </div>
    </header>

    <div class="pk-hrd-workflow-grid">
        @foreach ($statuses as $status)
            <div class="pk-hrd-status">
                <span class="pk-hrd-status__badge pk-hrd-status__badge--{{ $status['color'] }}">{{ $status['label'] }}</span>
                <strong class="pk-hrd-status__value">{{ number_format($status['count']) }}</strong>
            </div>
        @endforeach
    </div>
</section>
