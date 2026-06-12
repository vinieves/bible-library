@props([
    'percent' => 0,
    'compact' => false,
    'showLabel' => true,
    'status' => null,
])

@php
    $percent = max(0, min(100, (int) $percent));
    $status = $status ?? match (true) {
        $percent >= 100 => 'Estudiado',
        $percent > 0 => 'En progreso',
        default => 'Sin iniciar',
    };
    $fillClass = $percent >= 100 ? 'progress-bar-fill-complete' : 'progress-bar-fill-partial';
@endphp

<div {{ $attributes->merge(['class' => '']) }}>
    @if($showLabel)
        <div class="mb-1 flex items-center justify-between gap-2 text-xs text-bible-cream/60">
            <span>{{ $status }}</span>
            <span class="font-medium text-bible-gold">{{ $percent }}%</span>
        </div>
    @endif
    <div class="progress-bar-track {{ $compact ? 'h-1.5' : 'h-2.5' }}" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
        @if($percent > 0)
            <div class="{{ $fillClass }} h-full rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
        @endif
    </div>
</div>
