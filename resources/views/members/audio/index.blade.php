@extends('layouts.members', ['showBack' => true])

@section('title', 'Escuchar')

@section('content')
    <header class="mb-4">
        <h1 class="page-title mb-1 text-xl sm:text-2xl">Biblioteca en Audio</h1>
        <p class="text-sm leading-relaxed text-bible-cream/60 sm:text-base">
            Estudios y devocionales incluidos en su Plan Completo.
        </p>
    </header>

    <form method="GET" action="{{ route('members.audio.index') }}" class="library-filter mb-4">
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
                        <x-members.category-badge :category="$activeCategory" />
                    @endif
                @endif
                <a href="{{ route('members.audio.index') }}"
                   class="text-xs text-bible-cream/40 transition hover:text-bible-gold">
                    Limpiar
                </a>
            </div>
        @endif
    </form>

    <section class="audio-highlight mb-5 px-4 py-3.5 sm:px-5 sm:py-4">
        <p class="text-[0.65rem] font-medium uppercase tracking-wider text-bible-gold/80">Plan Completo</p>
        <p class="mt-0.5 text-sm text-bible-cream/75">
            Escuche donde quiera — ideal para el camino, el descanso o la oración.
        </p>
    </section>

    <section>
        <h2 class="section-title mb-2.5 text-base sm:text-lg">Audios disponibles</h2>
        @if($tracks->isEmpty())
            <p class="text-sm text-bible-cream/60">No se encontraron audios.</p>
        @else
            <div class="audio-list space-y-2">
                @foreach($tracks as $track)
                    <x-members.audio-card
                        :track="$track"
                        :progress="$progressByTrack->get($track->id)"
                        :locked="! auth()->user()->hasAccessToAudioTrack($track)"
                    />
                @endforeach
            </div>
        @endif
    </section>
@endsection
