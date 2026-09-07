<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-lg border border-transparent bg-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-primary-foreground shadow-sm transition duration-150 hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 active:translate-y-px']) }}>
    {{ $slot }}
</button>
