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
        'green' => 'from-bible-green/25 to-bible-green/5 text-bible-green',
        'gold' => 'from-bible-gold/20 to-bible-gold/5 text-bible-gold',
        default => 'from-bible-cream/10 to-bible-cream/5 text-bible-cream/70',
    };
@endphp

<a href="{{ $href }}"
   @class([
       'dashboard-nav-card group',
       'opacity-75' => $muted,
   ])>
    @if($coverUrl)
        <div class="aspect-square w-[4.25rem] shrink-0 overflow-hidden bg-bible-green/10 sm:w-[4.75rem]">
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
            <h3 class="text-base font-semibold leading-snug text-bible-gold sm:text-lg">{{ $title }}</h3>
            @if($badge)
                <span class="rounded-full bg-bible-gold/10 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-bible-gold/80">
                    {{ $badge }}
                </span>
            @endif
        </div>
        @if($subtitle)
            <p class="mt-0.5 line-clamp-2 text-sm leading-relaxed text-bible-cream/60">{{ $subtitle }}</p>
        @endif
    </div>

    <span class="mr-3 flex shrink-0 self-center items-center text-bible-gold/40 transition group-hover:translate-x-0.5 group-hover:text-bible-gold sm:mr-4">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </span>
</a>
