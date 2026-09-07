<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-lg border border-border bg-card px-4 py-2 text-xs font-semibold uppercase tracking-widest text-card-foreground shadow-sm transition duration-150 hover:bg-accent hover:text-accent-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
