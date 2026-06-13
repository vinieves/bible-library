@props([
    'video',
    'progress' => null,
    'locked' => false,
])

@php
    $user = auth()->user();
    $hasAccess = ! $locked && $user->hasAccessToVideo($video);
    $coverUrl = $video->coverUrl();
    $percent = $progress?->completionPercent($video) ?? 0;
    $statusLabel = $progress?->statusLabel($video) ?? null;
@endphp

<a href="{{ route('members.videos.show', $video) }}"
   @class([
       'video-list-item group',
       'video-list-item-locked' => ! $hasAccess,
   ])>
    <div class="video-list-thumb">
        @if($coverUrl)
            <img src="{{ $coverUrl }}"
                 alt=""
                 class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                 loading="lazy">
        @else
            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-bible-green/25 via-bible-dark to-bible-gold/10" aria-hidden="true">
                <svg class="h-7 w-7 text-bible-gold/70 sm:h-8 sm:w-8" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                </svg>
            </div>
        @endif

        <span class="video-list-thumb-play" aria-hidden="true">
            <span @class([
                'flex h-9 w-9 items-center justify-center rounded-full shadow-lg transition group-hover:scale-110 sm:h-10 sm:w-10',
                'bg-bible-green/90 text-white shadow-bible-green/25' => $hasAccess,
                'border border-bible-gold/30 bg-bible-black/60 text-bible-cream/60' => ! $hasAccess,
            ])>
                @if($hasAccess)
                    <svg class="ml-0.5 h-4 w-4 sm:h-[1.125rem] sm:w-[1.125rem]" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                @else
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                @endif
            </span>
        </span>

        @if($video->duration)
            <span class="video-list-duration">{{ $video->duration }}</span>
        @endif

        @if($hasAccess && $percent >= 100)
            <span class="absolute left-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-bible-green/90 text-white">
                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </span>
        @endif
    </div>

    <div class="video-list-body min-w-0 flex-1">
        <h3 class="line-clamp-2 text-sm font-semibold leading-snug text-bible-gold sm:text-base">
            {{ $video->title }}
        </h3>

        <p class="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-1">
            @if($video->category)
                <span class="inline-flex items-center rounded-full bg-bible-gold/10 px-2 py-0.5 text-xs font-medium text-bible-gold">
                    {{ $video->category->name }}
                </span>
            @endif
            @if(! $hasAccess)
                <span class="text-xs text-bible-cream/40">Plan Completo</span>
            @endif
        </p>

        @if($hasAccess && $percent > 0)
            <div class="mt-2">
                <x-members.progress-bar
                    :percent="$percent"
                    :status="$statusLabel"
                    compact
                    class="[&_.progress-bar-track]:h-1"
                />
            </div>
        @endif
    </div>

    <span @class([
        'video-list-action shrink-0',
        'text-bible-green' => $hasAccess,
        'text-bible-cream/35' => ! $hasAccess,
    ]) aria-hidden="true">
        @if($hasAccess)
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z"/>
            </svg>
        @else
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        @endif
    </span>
</a>
