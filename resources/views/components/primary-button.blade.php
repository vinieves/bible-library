<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-brown border border-transparent rounded-md font-semibold text-xs text-cream uppercase tracking-widest hover:bg-ink focus:bg-ink active:bg-ink focus:outline-none focus:ring-2 focus:ring-gold focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
