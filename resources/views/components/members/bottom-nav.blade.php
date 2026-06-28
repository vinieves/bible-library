@php
    $items = [
        ['route' => 'members.dashboard', 'label' => 'Inicio', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['route' => 'members.library', 'label' => 'Buscador', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
        ['route' => 'members.materials.index', 'label' => 'Materiales', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ['route' => 'members.forum.index', 'label' => 'Comunidad', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
        ['route' => 'members.audio.index', 'label' => 'Escuchar', 'icon' => 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z'],
    ];
@endphp

<nav class="fixed bottom-0 left-0 right-0 z-50 border-t border-brown/20 bg-cream/95 backdrop-blur-md md:hidden">
    <div class="mx-auto flex max-w-3xl justify-around px-2 py-2">
        @foreach($items as $item)
            @php
                $isActive = match ($item['route']) {
                    'members.audio.index' => request()->routeIs('members.audio.*'),
                    'members.materials.index' => request()->routeIs('members.materials.*'),
                    'members.forum.index' => request()->routeIs('members.forum.*'),
                    default => request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'),
                };
            @endphp
            <a href="{{ route($item['route']) }}"
               class="flex min-w-[4.5rem] flex-col items-center rounded-xl px-2 py-2 text-center transition
                      {{ $isActive ? 'bg-beige text-brown' : 'text-muted/70 hover:text-brown' }}">
                <svg class="mb-1 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                </svg>
                <span class="font-ui text-xs font-medium">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
