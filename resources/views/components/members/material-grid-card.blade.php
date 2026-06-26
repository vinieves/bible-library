@props([
    'material',
    'progress' => null,
])

@php
    $user = auth()->user();
    $hasAccess = $user->hasAccessToMaterial($material);
    $coverUrl = $material->coverUrl();
    $percent = $progress?->completionPercent($material) ?? 0;
    $href = ($hasAccess && $material->hasPdf())
        ? route('members.materials.pdf.reader', $material)
        : route('members.materials.show', $material);
@endphp

<a href="{{ $href }}" class="group block">
    <div class="relative aspect-[3/4] overflow-hidden rounded-2xl border border-line bg-cream shadow-sm transition duration-200 group-hover:-translate-y-0.5 group-hover:shadow-md">
        @if($coverUrl)
            <img src="{{ $coverUrl }}" alt="" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
        @else
            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brown/10 to-gold/5 text-5xl" aria-hidden="true">📖</div>
        @endif

        @if(! $hasAccess)
            <div class="absolute inset-0 flex items-center justify-center bg-ink/50">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-cream/90 text-brown">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </span>
            </div>
        @elseif($percent > 0)
            <div class="absolute inset-x-0 bottom-0 h-1 bg-ink/15">
                <div class="h-full bg-gold" style="width: {{ min(100, $percent) }}%"></div>
            </div>
        @endif
    </div>
    <p class="mt-2 truncate font-ui text-sm font-medium text-ink">{{ $material->title }}</p>
</a>
