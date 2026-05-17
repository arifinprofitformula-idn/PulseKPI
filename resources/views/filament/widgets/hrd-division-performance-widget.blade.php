<section class="pk-hrd-card">
    <header class="pk-hrd-card__header">
        <div>
            <h3 class="pk-hrd-card__title">Performa divisi</h3>
            <p class="pk-hrd-card__description">Rata-rata final score per divisi berdasarkan data yang tersedia.</p>
        </div>
    </header>

    @if ($rows === [])
        <div class="pk-hrd-empty">
            Data performa divisi belum tersedia untuk cakupan ini. Saat assessment memiliki data final score, ringkasannya akan muncul di sini.
        </div>
    @else
        <div class="pk-hrd-ranking">
            @foreach ($rows as $row)
                <article class="pk-hrd-ranking__item">
                    <div>
                        <h4 class="pk-hrd-ranking__title">{{ $row['name'] }}</h4>
                        <p class="pk-hrd-ranking__meta">{{ $row['assessment_count'] }} assessment</p>
                    </div>
                    <div class="pk-hrd-ranking__score">{{ $row['average_score'] }}</div>
                </article>
            @endforeach
        </div>
    @endif
</section>
