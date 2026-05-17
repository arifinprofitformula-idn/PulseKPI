<div class="pk-hrd-hero pk-role-hero pk-role-hero--manager">
    <div class="pk-hrd-hero__content">
        <div class="pk-hrd-hero__eyebrow">PulseKPI Manager Workspace</div>
        <h2 class="pk-hrd-hero__title">Welcome back, {{ $user->name }}.</h2>
        <p class="pk-hrd-hero__description">
            Keep your team moving by turning assigned KPI into complete assessments, revising rejected work quickly, and tracking which submissions are already out of your hands.
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
                Your available actions are limited by the current KPI workflow and your account permissions.
            </div>
        @endif
    </div>

    <div class="pk-hrd-hero__panel">
        <div class="pk-hrd-hero__metric-label">What to do next</div>
        <div class="pk-hrd-hero__metric-value">Focus on drafts and rejected items first, then use submitted rows as visibility into what is already waiting on HRD.</div>
        <div class="pk-hrd-hero__chips">
            <span class="pk-hrd-chip pk-hrd-chip--emerald">Direct reports only</span>
            <span class="pk-hrd-chip pk-hrd-chip--navy">Submitted stays read-only</span>
            <span class="pk-hrd-chip pk-hrd-chip--slate">No approver actions shown</span>
        </div>
    </div>
</div>
