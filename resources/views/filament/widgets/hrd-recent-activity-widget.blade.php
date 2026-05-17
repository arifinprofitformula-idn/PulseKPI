<section class="pk-hrd-card">
    <header class="pk-hrd-card__header">
        <div>
            <h3 class="pk-hrd-card__title">Aktivitas terbaru</h3>
            <p class="pk-hrd-card__description">Pembaruan operasional terakhir yang relevan untuk HRD.</p>
        </div>
    </header>

    @if ($activities === [])
        <div class="pk-hrd-empty">
            Belum ada aktivitas terbaru untuk ditampilkan. Aktivitas assessment, template, dan export akan muncul di sini.
        </div>
    @else
        <div class="pk-hrd-timeline">
            @foreach ($activities as $activity)
                <article class="pk-hrd-timeline__item">
                    <div class="pk-hrd-timeline__dot"></div>
                    <div>
                        <h4 class="pk-hrd-timeline__title">{{ $activity['title'] }}</h4>
                        <p class="pk-hrd-timeline__description">{{ $activity['description'] }}</p>
                    </div>
                    <span class="pk-hrd-timeline__time">{{ $activity['occurred_at'] }}</span>
                </article>
            @endforeach
        </div>
    @endif
</section>
