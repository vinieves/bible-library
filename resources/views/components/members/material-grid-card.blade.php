@props([
    'material',
    'progress' => null,
])

@php
    $user = auth()->user();
    $hasAccess = $user->hasAccessToMaterial($material);
    $coverUrl = $material->coverUrl();
    $percent = $progress?->completionPercent($material) ?? 0;
    $canPrint = $hasAccess && $material->hasPdf();
    $href = $canPrint
        ? route('members.materials.pdf.reader', $material)
        : route('members.materials.show', $material);
    $printUrl = $canPrint ? route('members.materials.pdf.stream', $material) : null;
@endphp

<a
    href="{{ $href }}"
    class="group block"
    @click="if (selectMode) { $event.preventDefault(); @if($canPrint) toggleSelected({{ $material->id }}, @js($printUrl)); @endif } @if(! $hasAccess) else { $event.preventDefault(); $dispatch('open-modal', 'upsell-{{ $material->id }}'); } @endif"
>
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
            <div class="absolute inset-x-0 bottom-0 h-2.5 bg-ink/20">
                <div class="h-full bg-gold" style="width: {{ min(100, $percent) }}%"></div>
            </div>
        @endif

        @if($canPrint)
            <div
                x-show="selectMode"
                x-cloak
                class="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full border-2 border-cream bg-ink/40"
                :class="isSelected({{ $material->id }}) ? 'border-gold bg-gold' : ''"
            >
                <svg x-show="isSelected({{ $material->id }})" class="h-3.5 w-3.5 text-ink" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
        @endif
    </div>
    <p class="mt-2 truncate font-ui text-sm font-medium text-ink">{{ $material->title }}</p>
</a>

@if(! $hasAccess)
    <x-members.upsell-modal :material="$material" />
@endif
