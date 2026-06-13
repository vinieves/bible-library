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
@endphp

<div @class([
    'member-card overflow-hidden p-0',
    'opacity-90' => $locked,
])>
    <div class="flex items-stretch">
        <div class="relative aspect-video w-32 shrink-0 overflow-hidden bg-bible-green/10 sm:w-36">
            @if($coverUrl)
                <img src="{{ $coverUrl }}"
                     alt="{{ $video->title }}"
                     class="h-full w-full object-cover"
                     loading="lazy">
            @else
                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-bible-green/20 to-bible-gold/10 text-3xl sm:text-4xl">
                    ▶
                </div>
            @endif
            @if($hasAccess)
                <span class="absolute inset-0 flex items-center justify-center bg-black/25 opacity-0 transition hover:opacity-100">
                    <span class="rounded-full bg-bible-green/90 p-2.5 text-white">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                    </span>
                </span>
            @endif
        </div>

        <div class="flex min-w-0 flex-1 flex-col justify-center px-4 py-3.5 sm:px-5 sm:py-4">
            <h3 class="text-base font-semibold leading-snug text-bible-gold sm:text-lg">
                {{ $video->title }}
            </h3>
            <p class="mt-1 line-clamp-2 text-sm leading-relaxed text-bible-cream/70 sm:text-base">
                {{ $video->description }}
            </p>

            <div class="mt-2 flex flex-wrap gap-1.5">
                @if($video->category)
                    <span class="rounded-full bg-bible-gold/10 px-2.5 py-0.5 text-xs font-medium text-bible-gold">
                        {{ $video->category->name }}
                    </span>
                @endif
                @if($video->duration)
                    <span class="rounded-full bg-white/5 px-2.5 py-0.5 text-xs text-bible-cream/50">
                        {{ $video->duration }}
                    </span>
                @endif
            </div>

            @if($hasAccess && $percent > 0)
                <x-members.progress-bar
                    class="mt-2.5"
                    :percent="$percent"
                    :status="$statusLabel"
                    compact
                />
            @endif

            <div class="mt-3">
                @if($hasAccess)
                    <a href="{{ route('members.videos.show', $video) }}"
                       class="btn-audio-action btn-audio-action-primary inline-flex">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                        Reproducir
                    </a>
                @else
                    <a href="{{ route('members.videos.show', $video) }}"
                       class="btn-audio-action inline-flex">
                        Desbloquear video
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
