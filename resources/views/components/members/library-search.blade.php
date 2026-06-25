@props([
    'action',
    'search' => '',
    'categories' => collect(),
    'categoryId' => null,
    'placeholder' => 'Buscar por título…',
])

<form method="GET" action="{{ $action }}" {{ $attributes->class(['library-search-form']) }}>
    <div class="library-search-bar">
        <svg class="library-search-bar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="search"
               name="q"
               value="{{ $search }}"
               placeholder="{{ $placeholder }}"
               class="library-search-bar-input"
               enterkeyhint="search">
        @if($categoryId)
            <input type="hidden" name="categoria" value="{{ $categoryId }}">
        @endif
    </div>

    @if($categories->isNotEmpty())
        <select name="categoria"
                class="library-search-category mt-2.5"
                onchange="this.form.submit()">
            <option value="">Todas las categorías</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected($categoryId == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    @endif

    @if($search || $categoryId)
        <div class="library-search-active mt-2.5 flex flex-wrap items-center gap-2">
            @if($search)
                <span class="inline-flex items-center rounded-full bg-gold/10 px-2.5 py-0.5 text-xs text-gold">
                    «{{ Str::limit($search, 24) }}»
                </span>
            @endif
            @if($categoryId)
                @php $activeCategory = $categories->firstWhere('id', (int) $categoryId); @endphp
                @if($activeCategory)
                    <x-members.category-badge :category="$activeCategory" />
                @endif
            @endif
            <a href="{{ $action }}" class="text-xs text-tan transition hover:text-gold">
                Limpiar
            </a>
        </div>
    @endif
</form>
