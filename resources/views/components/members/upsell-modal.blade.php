@props(['material'])

@php
    $title = $material->upsell_title ?: $material->title;
    $subtitle = $material->upsell_subtitle ?: $material->description;
    $gallery = $material->upsellGalleryUrls();
    $videoExtensions = ['mp4', 'webm', 'mov', 'ogg'];
@endphp

<x-modal :name="'upsell-' . $material->id" maxWidth="lg">
    <div class="px-6 py-6 text-center">
        <div class="mb-4 text-5xl">🔒</div>

        <h2 class="font-display text-2xl font-bold text-ink">{{ $title }}</h2>

        @if($subtitle)
            <p class="mt-3 text-sm text-muted">{{ $subtitle }}</p>
        @endif

        @if(count($gallery))
            <div class="mt-5 grid {{ count($gallery) > 1 ? 'grid-cols-2' : 'grid-cols-1' }} gap-2">
                @foreach($gallery as $url)
                    @if(in_array(strtolower(pathinfo($url, PATHINFO_EXTENSION)), $videoExtensions))
                        <video src="{{ $url }}" controls class="aspect-video w-full rounded-lg bg-ink/10 object-cover"></video>
                    @else
                        <img src="{{ $url }}" alt="" class="aspect-video w-full rounded-lg object-cover">
                    @endif
                @endforeach
            </div>
        @endif

        <a
            href="{{ route('members.materials.checkout.redirect', $material) }}"
            class="btn-gold mt-6 inline-flex w-full justify-center"
        >
            Desbloquear Material
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
