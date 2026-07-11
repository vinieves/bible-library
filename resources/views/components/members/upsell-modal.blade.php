@props(['material'])

<x-modal :name="'upsell-' . $material->id" maxWidth="sm">
    <div class="px-6 py-6 text-center">
        <div class="mb-4 text-5xl">🔒</div>

        <h2 class="font-display text-xl font-bold text-ink">{{ $material->title }}</h2>

        <p class="mt-3 text-sm text-muted">
            Este contenido es exclusivo. Desbloquéelo para leer <strong class="text-gold">{{ $material->title }}</strong> completo.
        </p>

        <a
            href="{{ route('members.materials.checkout.redirect', $material) }}"
            class="btn-gold mt-6 inline-flex w-full justify-center"
        >
            Desbloquear
        </a>

        <button
            type="button"
            class="mt-3 text-sm text-muted underline"
            x-on:click="$dispatch('close-modal', 'upsell-{{ $material->id }}')"
        >
            Ahora no
        </button>

        <p class="mt-6 text-xs text-muted">
            ¿Ya compró? Su acceso se activará automáticamente después de la confirmación del pago.
        </p>
    </div>
</x-modal>
