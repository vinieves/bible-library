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
    $statusLabel = $progress?->statusLabel($video) ?? 'Sin iniciar';
    $iconClasses = $video->category?->iconThumbClasses() ?? 'icon-thumb-gold';
@endphp

<a href="{{ route('members.videos.show', $video) }}"
   @class([
       'member-card group block overflow-hidden p-0',
       'opacity-90' => ! $hasAccess,
   ])>
    <div class="flex items-stretch">
        <div class="video-list-thumb">
            @if($coverUrl)
                <img src="{{ $coverUrl }}"
                     alt=""
                     class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                     loading="lazy">
            @else
                <div @class(['flex h-full w-full items-center justify-center', $iconClasses]) aria-hidden="true">
                    <svg class="h-8 w-8 sm:h-9 sm:w-9" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </div>
            @endif

            <span class="video-list-thumb-play" aria-hidden="true">
                <span @class([
                    'flex h-10 w-10 items-center justify-center rounded-full shadow-lg transition group-hover:scale-110 sm:h-11 sm:w-11',
                    'bg-bible-green/90 text-white shadow-bible-green/25' => $hasAccess,
                    'border border-bible-gold/30 bg-bible-black/60 text-bible-cream/60' => ! $hasAccess,
                ])>
                    @if($hasAccess)
                        <svg class="ml-0.5 h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
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
                <span class="absolute left-1.5 top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-bible-green/90 text-white">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
            @endif
        </div>

        <div class="flex min-w-0 flex-1 flex-col justify-center px-4 py-3.5 sm:px-5 sm:py-4">
            <h3 class="text-base font-medium leading-snug text-bible-cream sm:text-lg">
                {{ $video->title }}
            </h3>

            @if(filled($video->description))
                <p class="mt-1.5 line-clamp-2 text-sm leading-relaxed text-bible-muted-warm sm:text-base">
                    {{ $video->description }}
                </p>
            @endif

            <div class="mt-2 flex flex-wrap gap-1.5">
                @if($video->category)
                    <x-members.category-badge :category="$video->category" />
                @endif
                @if(! $hasAccess)
                    <span class="badge-tone-rose inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">Bloqueado</span>
                @endif
            </div>

            @if($hasAccess)
                <x-members.progress-bar
                    class="mt-2.5"
                    :percent="$percent"
                    :status="$statusLabel"
                    compact
                />
            @endif
        </div>
    </div>
</a>
