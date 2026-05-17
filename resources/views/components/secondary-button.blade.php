<button {{ $attributes->merge(['type' => 'button', 'class' => 'pulse-button-secondary text-sm tracking-[0.16em] uppercase disabled:cursor-not-allowed disabled:opacity-60']) }}>
    {{ $slot }}
</button>
