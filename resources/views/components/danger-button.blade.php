<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-lg border border-transparent bg-destructive px-4 py-2 text-xs font-semibold uppercase tracking-widest text-destructive-foreground shadow-sm transition duration-150 hover:bg-destructive/90 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:translate-y-px']) }}>
    {{ $slot }}
</button>
