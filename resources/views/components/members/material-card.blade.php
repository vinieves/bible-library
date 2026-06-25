@props(['material', 'showProgress' => false])

@php
    $user = auth()->user();
    $hasAccess = $user->hasAccessToMaterial($material);
    $progress = $showProgress
        ? $user->materialProgress()->where('material_id', $material->id)->first()
        : null;
@endphp

<a href="{{ route('members.materials.show', $material) }}"
   class="member-card {{ ! $hasAccess ? 'opacity-80' : '' }}">
    <div class="flex gap-4">
        <div class="flex h-20 w-16 shrink-0 items-center justify-center rounded-lg bg-brown/20 text-3xl">
            📖
        </div>
        <div class="min-w-0 flex-1">
            <h3 class="text-lg font-semibold leading-snug text-gold">{{ $material->title }}</h3>
            <p class="mt-1 line-clamp-2 text-sm text-muted">{{ $material->description }}</p>
            <div class="mt-2 flex flex-wrap gap-2">
                <span class="rounded-full bg-gold/10 px-3 py-1 text-xs text-gold">
                    {{ $material->category->name }}
                </span>
                @if(! $hasAccess)
                    <span class="rounded-full bg-red-900/40 px-3 py-1 text-xs text-red-300">Bloqueado</span>
                @elseif($material->hasPdf())
                    <span class="rounded-full bg-brown/30 px-3 py-1 text-xs text-brown">PDF</span>
                @elseif($progress?->is_studied)
                    <span class="rounded-full bg-brown/30 px-3 py-1 text-xs text-brown">Estudiado</span>
                @endif
            </div>
        </div>
    </div>
</a>
