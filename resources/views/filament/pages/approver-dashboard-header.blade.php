<div class="pk-hrd-hero pk-role-hero pk-role-hero--approver">
    <div class="pk-hrd-hero__content">
        <div class="pk-hrd-hero__eyebrow">PulseKPI Approver Workspace</div>
        <h2 class="pk-hrd-hero__title">Welcome back, {{ $user->name }}.</h2>
        <p class="pk-hrd-hero__description">
            Review only the assessments that are ready for sign-off, keep approval decisions clear, and finalize the records that have already passed review.
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
                Your approval queue follows the workflow exactly, so only eligible records are shown here.
            </div>
        @endif
    </div>

    <div class="pk-hrd-hero__panel">
        <div class="pk-hrd-hero__metric-label">Approval focus</div>
        <div class="pk-hrd-hero__metric-value">Prioritize reviewed assessments, then close the loop with approve, reject, or lock without exposing workflow stages outside your scope.</div>
        <div class="pk-hrd-hero__chips">
            <span class="pk-hrd-chip pk-hrd-chip--emerald">Reviewed queue only</span>
            <span class="pk-hrd-chip pk-hrd-chip--navy">Private files stay hidden</span>
            <span class="pk-hrd-chip pk-hrd-chip--slate">No manager edit actions</span>
        </div>
    </div>
</div>
