@extends('layouts.members', ['showBack' => true])

@section('title', 'Mi biblioteca')

@section('content')
    <form method="GET" action="{{ route('members.library') }}" class="library-filter mb-5">
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
                <a href="{{ route('members.library') }}"
                   class="text-xs text-bible-cream/40 transition hover:text-bible-gold">
                    Limpiar
                </a>
            </div>
        @endif
    </form>

    @if($materials->isEmpty())
        <p class="text-center text-base text-bible-cream/60">No se encontraron materiales.</p>
    @else
        <div class="space-y-3">
            @foreach($materials as $material)
                <x-members.book-card
                    :material="$material"
                    :progress="$progressByMaterial->get($material->id)"
                />
            @endforeach
        </div>
    @endif
@endsection
