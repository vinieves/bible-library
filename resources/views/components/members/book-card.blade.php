@props(['material', 'progress' => null])

@php
    $user = auth()->user();
    $hasAccess = $user->hasAccessToMaterial($material);
    $coverUrl = $material->coverUrl();
    $percent = $progress?->completionPercent($material) ?? 0;
    $statusLabel = $progress?->statusLabel($material) ?? 'Sin iniciar';
@endphp

<a href="{{ route('members.materials.show', $material) }}"
   class="member-card block overflow-hidden p-0 {{ ! $hasAccess ? 'opacity-80' : '' }}">
    <div class="flex items-stretch">
        {{-- Capa à esquerda --}}
        <div class="relative aspect-square w-28 shrink-0 overflow-hidden bg-brown/10 sm:w-32">
            @if($coverUrl)
                <img src="{{ $coverUrl }}"
                     alt="{{ $material->title }}"
                     class="h-full w-full object-cover"
                     loading="lazy">
            @else
                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brown/20 to-gold/10 text-3xl sm:text-4xl">
                    📖
                </div>
            @endif
            @if($percent >= 100)
                <span class="absolute bottom-1 right-1 rounded-full bg-brown/90 p-1 text-cream">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
            @endif
        </div>

        {{-- Textos à direita --}}
        <div class="flex min-w-0 flex-1 flex-col justify-center px-4 py-3.5 sm:px-5 sm:py-4">
            <h3 class="text-base font-semibold leading-snug text-gold sm:text-lg">
                {{ $material->title }}
            </h3>
            <p class="mt-1 line-clamp-2 text-sm leading-relaxed text-muted sm:text-base">
                {{ $material->description }}
            </p>
            <div class="mt-2 flex flex-wrap gap-1.5">
                <span class="rounded-full bg-gold/10 px-2.5 py-0.5 text-xs font-medium text-gold">
                    {{ $material->category->name }}
                </span>
                @if(! $hasAccess)
                    <span class="rounded-full bg-red-900/40 px-2.5 py-0.5 text-xs text-red-300">Bloqueado</span>
                @elseif($material->hasPdf())
                    <span class="rounded-full bg-brown/30 px-2.5 py-0.5 text-xs text-brown">PDF</span>
                @endif
            </div>
            <x-members.progress-bar
                class="mt-2.5"
                :percent="$percent"
                :status="$statusLabel"
                compact
            />
        </div>
    </div>
</a>
