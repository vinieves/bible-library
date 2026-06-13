@props([
    'category' => null,
    'fallback' => 'Sin categoría',
])

@if($category)
    <span {{ $attributes->class([
        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
        $category->badgeColorClasses(),
    ]) }}>
        {{ $category->name }}
    </span>
@else
    <span {{ $attributes->class([
        'inline-flex items-center rounded-full bg-white/5 px-2.5 py-0.5 text-xs font-medium text-bible-cream/45',
    ]) }}>
        {{ $fallback }}
    </span>
@endif
