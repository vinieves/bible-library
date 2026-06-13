@props([
    'icon' => 'audio',
    'title',
    'message',
])

<div {{ $attributes->class(['member-empty-state']) }}>
    <div class="member-empty-state-glow" aria-hidden="true">
        <div @class([
            'member-empty-state-icon',
            'member-empty-state-icon-video' => $icon === 'video',
            'member-empty-state-icon-audio' => $icon === 'audio',
        ])>
            @if($icon === 'video')
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            @else
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"/>
                </svg>
            @endif
        </div>
    </div>

    <h2 class="member-empty-state-title">{{ $title }}</h2>
    <p class="member-empty-state-message">{{ $message }}</p>
</div>
