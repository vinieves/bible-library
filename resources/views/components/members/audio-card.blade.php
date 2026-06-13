@props([
    'track',
    'progress' => null,
    'locked' => false,
])

@php
    $user = auth()->user();
    $hasAccess = ! $locked && $user->hasAccessToAudioTrack($track);
    $coverUrl = $track->coverUrl();
    $percent = $progress?->completionPercent($track) ?? 0;
    $statusLabel = $progress?->statusLabel($track) ?? null;
@endphp

<a href="{{ route('members.audio.show', $track) }}"
   @class([
       'audio-list-item group',
       'audio-list-item-locked' => ! $hasAccess,
   ])>
    <div class="audio-list-thumb">
        @if($coverUrl)
            <img src="{{ $coverUrl }}"
                 alt=""
                 class="h-full w-full object-cover"
                 loading="lazy">
        @else
            <span class="flex h-full w-full items-center justify-center text-lg text-bible-gold/80" aria-hidden="true">🎧</span>
        @endif
    </div>

    <div class="audio-list-body min-w-0 flex-1">
        <h3 class="truncate text-sm font-semibold text-bible-gold sm:text-base">
            {{ $track->title }}
        </h3>

        <p class="mt-0.5 flex flex-wrap items-center gap-x-1.5 text-xs text-bible-cream/50">
            @if($track->category)
                <span>{{ $track->category->name }}</span>
                @if($track->duration)
                    <span aria-hidden="true">·</span>
                @endif
            @endif
            @if($track->duration)
                <span>{{ $track->duration }}</span>
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
        'audio-list-action shrink-0',
        'text-bible-green' => $hasAccess,
        'text-bible-cream/35' => ! $hasAccess,
    ]) aria-hidden="true">
        @if($hasAccess)
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z"/>
            </svg>
        @else
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        @endif
    </span>
</a>
