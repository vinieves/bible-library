@extends('layouts.members', ['showBack' => true])

@section('title', 'Videos')

@section('content')
    @if($videos->isEmpty() && ! $search && ! $categoryId)
        <p class="mb-4 text-sm text-bible-cream/60">Estudios y enseñanzas en video de su biblioteca.</p>
    @endif

    <form method="GET" action="{{ route('members.videos.index') }}" class="library-filter mb-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-stretch sm:gap-2.5">
            <div class="library-filter-field">
                <button type="submit"
                        class="library-filter-search-btn"
                        aria-label="Buscar">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
                <input type="search"
                       name="q"
                       value="{{ $search }}"
                       placeholder="Buscar por título…"
                       class="library-filter-input"
                       enterkeyhint="search">
                <button type="submit" class="library-filter-search-submit">
                    Buscar
                </button>
            </div>

            <select name="categoria"
                    class="library-filter-select sm:w-44 md:w-52"
                    onchange="this.form.submit()">
                <option value="">Todas las categorías</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected($categoryId == $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        @if($search || $categoryId)
            <div class="mt-2.5 flex flex-wrap items-center gap-2 border-t border-white/5 pt-2.5">
                @if($search)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-bible-gold/10 px-2.5 py-0.5 text-xs text-bible-gold/90">
                        «{{ Str::limit($search, 24) }}»
                    </span>
                @endif
                @if($categoryId)
                    @php $activeCategory = $categories->firstWhere('id', (int) $categoryId); @endphp
                    @if($activeCategory)
                        <span class="inline-flex items-center rounded-full bg-bible-green/15 px-2.5 py-0.5 text-xs text-green-300/90">
                            {{ $activeCategory->name }}
                        </span>
                    @endif
                @endif
                <a href="{{ route('members.videos.index') }}"
                   class="text-xs text-bible-cream/40 transition hover:text-bible-gold">
                    Limpiar
                </a>
            </div>
        @endif
    </form>

    <section>
        @if($videos->isEmpty())
            <h2 class="section-title mb-2.5 text-base sm:text-lg">Videos disponibles</h2>
            <p class="text-sm text-bible-cream/60">
                @if($search || $categoryId)
                    No se encontraron videos.
                @else
                    Próximamente nuevos videos en la biblioteca.
                @endif
            </p>
        @else
            <div class="video-list space-y-3">
                @foreach($videos as $video)
                    @php
                        $hasAccess = auth()->user()->hasAccessToVideo($video);
                    @endphp
                    <x-members.video-card
                        :video="$video"
                        :progress="$progressByVideo->get($video->id)"
                        :locked="! $hasAccess"
                    />
                @endforeach
            </div>
        @endif
    </section>
@endsection
