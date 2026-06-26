@extends('layouts.members-reader')

@section('title', $material->title)

@push('scripts')
    @vite(['resources/js/pdf-reader.js'])
@endpush

@section('content')
    @php
        $initialPage = max(1, $progress->current_page ?: ($progress->last_page_read ?: 1));
    @endphp

    <div class="flex min-h-0 flex-1 flex-col"
         data-pdf-reader
         data-pdf-url="{{ route('members.materials.pdf.stream', $material) }}"
         data-save-url="{{ route('members.materials.pdf.progress', $material) }}"
         data-initial-page="{{ $initialPage }}"
         data-max-page-read="{{ $progress->last_page_read }}"
         data-total-pages="{{ $material->pdf_page_count ?? 0 }}">

        <div class="pdf-reader-container relative min-h-0 flex-1 bg-brown-deep" data-pdf-touch-area>
            <div data-pdf-canvas-wrap class="absolute inset-0 overflow-x-hidden overflow-y-auto overscroll-contain">
                <canvas data-pdf-canvas class="mx-auto block w-full max-w-full"></canvas>
            </div>
            <embed data-pdf-embed
                   type="application/pdf"
                   class="pdf-reader-embed hidden"
                   title="{{ $material->title }}">
            <p data-pdf-loading class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center text-sm text-cream/50">
                Cargando PDF…
            </p>
            <p data-pdf-error class="pointer-events-none absolute inset-0 z-10 flex hidden items-center justify-center p-6 text-center text-sm text-cream/60">
                No se pudo cargar el PDF.
                <a href="{{ route('members.materials.pdf.stream', $material) }}"
                   class="mt-3 block text-gold underline">
                    Abrir en el navegador
                </a>
            </p>
        </div>

        <div class="reader-toolbar shrink-0 border-t border-gold/20 bg-brown-deep/95 px-3 py-3 backdrop-blur sm:px-4">
            <div class="mx-auto flex max-w-3xl items-center justify-between gap-3">
                <div data-pdf-page-controls class="flex flex-1 items-center justify-center gap-2 sm:justify-start sm:gap-1.5">
                    <button type="button"
                            data-page-prev
                            class="btn-reader-action min-h-12 min-w-12 px-3.5 sm:min-h-11 sm:min-w-11 sm:px-3"
                            aria-label="Página anterior">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <span class="min-w-[5.5rem] text-center text-sm text-cream/80 sm:text-base">
                        <span data-page-current>{{ $initialPage }}</span>
                        /
                        <span data-page-total>{{ $material->pdf_page_count ?: '—' }}</span>
                    </span>
                    <button type="button"
                            data-page-next
                            class="btn-reader-action min-h-12 min-w-12 px-3.5 sm:min-h-11 sm:min-w-11 sm:px-3"
                            aria-label="Página siguiente">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>

                <a href="{{ route('members.materials.pdf.download', $material) }}"
                   class="btn-reader-action"
                   title="Descargar PDF">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>Descargar</span>
                </a>
            </div>
        </div>
    </div>
@endsection
