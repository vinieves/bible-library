@props([
    'track',
    'progress' => null,
    'locked' => false,
])

@php
    $user = auth()->user();
    $hasAccess = ! $locked && $user->hasAccessToAudioTrack($track);
    $percent = $progress?->completionPercent($track) ?? 0;
    $statusLabel = $progress?->statusLabel($track) ?? null;
    $iconClasses = $track->category?->iconThumbClasses() ?? 'icon-thumb-gold';
@endphp

<a href="{{ route('members.audio.show', $track) }}"
   @class([
       'audio-list-item group',
       'audio-list-item-locked' => ! $hasAccess,
   ])>
    <div @class(['audio-list-thumb flex items-center justify-center', $iconClasses])>
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"/>
        </svg>
    </div>

    <div class="audio-list-body min-w-0 flex-1">
        <h3 class="truncate text-[0.9375rem] font-medium leading-snug text-ink sm:text-base">
            {{ $track->title }}
        </h3>

        <div class="mt-1.5">
            <x-members.category-badge :category="$track->category" />
        </div>

        @if($hasAccess && $percent > 0)
            <div class="mt-2.5">
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
        'audio-list-play shrink-0',
        'text-gold' => $hasAccess,
        'text-tan' => ! $hasAccess,
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
