@extends('layouts.members', ['showBack' => true])

@section('title', $material->title)

@section('content')
    @php
        $coverUrl = $material->coverUrl();
        $isBonus = $material->type->value === 'bonus';
    @endphp

    <article class="overflow-hidden rounded-2xl border border-bible-gold/20 bg-bible-dark shadow-lg">
        {{-- Capa --}}
        <div class="mx-auto aspect-square w-full max-w-md overflow-hidden bg-bible-green/10 sm:max-w-lg">
            @if($coverUrl)
                <img src="{{ $coverUrl }}"
                     alt="{{ $material->title }}"
                     class="h-full w-full object-cover">
            @else
                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-bible-green/20 to-bible-gold/10 text-7xl">
                    {{ $isBonus ? '🎁' : '📖' }}
                </div>
            @endif
        </div>

        {{-- Info --}}
        <div class="border-t border-bible-gold/10 p-5 sm:p-6">
            <div class="mb-3 flex flex-wrap gap-2">
                <span class="rounded-full bg-bible-gold/10 px-3 py-1 text-xs font-medium text-bible-gold">
                    {{ $material->category->name }}
                </span>
                @if($material->hasPdf())
                    <span class="rounded-full bg-bible-green/20 px-3 py-1 text-xs font-medium text-green-300">PDF</span>
                @endif
                @if($progress->is_studied)
                    <span class="rounded-full bg-bible-green/30 px-3 py-1 text-xs font-medium text-green-200">Estudiado</span>
                @endif
            </div>

            <p class="text-base leading-relaxed text-bible-cream/85 sm:text-lg">
                {{ $material->description }}
            </p>

            <x-members.progress-bar
                class="mt-4"
                :percent="$progress->completionPercent($material)"
                :status="$progress->statusLabel($material)"
            />
        </div>
    </article>

    {{-- Ações principais --}}
    @if($material->hasPdf())
        <div class="mt-5 space-y-3">
            <a href="{{ route('members.materials.pdf.reader', $material) }}"
               class="btn-material-primary">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                Leer PDF
            </a>

            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('members.materials.pdf.download', $material) }}"
                   class="btn-material-secondary">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Descargar
                </a>
                <a href="{{ route('members.materials.pdf.stream', $material) }}"
                   target="_blank"
                   rel="noopener"
                   class="btn-material-secondary">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Abrir
                </a>
            </div>
        </div>
    @endif

    {{-- Ação secundária --}}
    <form method="POST" action="{{ route('members.materials.toggle-studied', $material) }}" class="mt-4">
        @csrf
        <button type="submit"
                class="btn-material-ghost w-full {{ $progress->is_studied ? 'btn-material-ghost-active' : '' }}">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            {{ $progress->is_studied ? 'Estudiado' : 'Marcar estudiado' }}
        </button>
    </form>

    @if($material->content)
        <section class="mt-6 rounded-2xl border border-bible-gold/20 bg-bible-dark p-5 sm:p-6">
            <h2 class="section-title mb-4">Sobre este material</h2>
            <div class="prose prose-invert max-w-none text-base leading-relaxed text-bible-cream/90 sm:text-lg">
                {!! $material->content !!}
            </div>
        </section>
    @endif
@endsection
