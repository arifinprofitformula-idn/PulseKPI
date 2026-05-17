@props([
    'title',
    'description' => null,
])

<section {{ $attributes->merge(['class' => 'w-full rounded-2xl border border-slate-200/70 bg-white p-6 shadow-lg shadow-slate-900/[0.06] sm:p-8']) }}>

    {{-- Logo: centered on mobile, left on desktop --}}
    <div class="mb-7 flex flex-col items-center md:flex-row md:items-center md:justify-between md:gap-4">
        <a href="{{ url('/') }}" class="rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-300 focus:ring-offset-2">
            <img
                src="{{ asset(config('branding.assets.logo_full', config('branding.logo_full'))) }}"
                alt="{{ config('branding.name', config('app.name')) }}"
                class="h-[100px] w-[100px] object-contain"
            >
        </a>

        <span class="mt-3 inline-flex shrink-0 items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-emerald-700 md:mt-0">
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            Secure
        </span>
    </div>

    {{-- Title & description --}}
    <div class="space-y-1.5 text-center md:text-left">
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
