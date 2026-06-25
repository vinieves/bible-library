@props([
    'href',
    'title',
    'subtitle' => null,
    'icon' => null,
    'accent' => 'gold',
    'material' => null,
    'badge' => null,
    'muted' => false,
])

@php
    $coverUrl = $material?->coverUrl();
    $accentClasses = match ($accent) {
        'green' => 'from-brown/20 to-brown/5 text-brown',
        'gold' => 'from-brown/20 to-brown/5 text-brown',
        default => 'from-brown/10 to-brown/5 text-muted',
    };
@endphp

<a href="{{ $href }}"
   @class([
       'dashboard-nav-card group',
       'opacity-75' => $muted,
   ])>
    @if($coverUrl)
        <div class="aspect-square w-[4.25rem] shrink-0 overflow-hidden bg-brown/10 sm:w-[4.75rem]">
            <img src="{{ $coverUrl }}"
                 alt=""
                 class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                 loading="lazy">
        </div>
    @elseif($icon)
        <div @class([
            'dashboard-nav-icon bg-gradient-to-br',
            $accentClasses,
        ])>
            <span class="text-2xl sm:text-3xl">{{ $icon }}</span>
        </div>
    @endif

    <div class="min-w-0 flex-1 py-3.5 pr-2 sm:py-4">
        <div class="flex flex-wrap items-center gap-2">
            <h3 class="text-base font-semibold leading-snug text-ink sm:text-lg">{{ $title }}</h3>
            @if($badge)
                <span class="rounded-full bg-brown/10 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-brown">
                    {{ $badge }}
                </span>
            @endif
        </div>
        @if($subtitle)
            <p class="mt-0.5 line-clamp-2 text-sm leading-relaxed text-muted">{{ $subtitle }}</p>
        @endif
    </div>

    <span class="mr-3 flex shrink-0 self-center items-center text-brown/40 transition group-hover:translate-x-0.5 group-hover:text-brown sm:mr-4">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </span>
</a>
