<button {{ $attributes->merge(['type' => 'submit', 'class' => 'pulse-button-primary border-0 text-sm tracking-[0.16em] uppercase disabled:cursor-not-allowed disabled:opacity-60']) }}>
    {{ $slot }}
</button>
