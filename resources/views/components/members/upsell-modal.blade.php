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

        @if($material->hasPreviewPdf())
            @once
                @push('scripts')
                    @vite(['resources/js/pdf-preview.js'])
                @endpush
            @endonce

            <div
                data-pdf-preview
                data-modal-name="upsell-{{ $material->id }}"
                data-pdf-url="{{ route('members.materials.pdf.preview', $material) }}"
                data-max-pages="5"
                data-initial-page="1"
                data-total-pages="0"
                class="mt-5 text-left"
            >
                <div data-pdf-canvas-wrap class="relative max-h-80 overflow-y-auto overflow-x-hidden rounded-lg border border-line bg-cream">
                    <canvas data-pdf-canvas class="mx-auto block w-full max-w-full"></canvas>
                    <p data-pdf-loading class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center text-sm text-muted">
                        Cargando vista previa…
                    </p>
                    <p data-pdf-error class="pointer-events-none absolute inset-0 z-10 hidden items-center justify-center p-4 text-center text-sm text-muted">
                        No se pudo cargar la vista previa.
                    </p>
                </div>
                <div class="mt-2 flex items-center justify-center gap-4 text-xs text-muted">
                    <button type="button" data-page-prev class="rounded-full border border-line px-3 py-1 disabled:opacity-30">‹</button>
                    <span><span data-page-current>1</span> / <span data-page-total>—</span></span>
                    <button type="button" data-page-next class="rounded-full border border-line px-3 py-1 disabled:opacity-30">›</button>
                </div>
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
