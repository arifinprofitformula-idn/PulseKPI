@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold tracking-[0.01em] text-slate-800']) }}>
    {{ $value ?? $slot }}
</label>
