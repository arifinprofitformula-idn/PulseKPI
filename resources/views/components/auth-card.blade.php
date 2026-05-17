@props([
    'title',
    'description' => null,
])

<section {{ $attributes->merge(['class' => 'w-full rounded-2xl border border-slate-200/70 bg-white p-6 shadow-lg shadow-slate-900/[0.06] sm:p-8']) }}>
    {{-- Card header: brand + secure badge --}}
    <div class="mb-7 flex items-center justify-between gap-4">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-300 focus:ring-offset-2">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-navy-600 p-2 shadow-md shadow-brand-500/25">
                <img
                    src="{{ asset(config('branding.assets.logo_mark', config('branding.logo_mark'))) }}"
                    alt="{{ config('branding.name', config('app.name')) }}"
                    class="h-6 w-6 object-contain"
                >
            </span>
            <span class="text-base font-bold tracking-tight text-slate-900">
                {{ config('branding.name', config('app.name')) }}
            </span>
        </a>

        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-emerald-700">
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            Secure
        </span>
    </div>

    {{-- Title & description --}}
    <div class="space-y-1.5">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
            {{ $title }}
        </h1>

        @if ($description)
            <p class="text-sm leading-6 text-slate-500">
                {{ $description }}
            </p>
        @endif
    </div>

    <div class="mt-7">
        {{ $slot }}
    </div>
</section>
