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
        'badge-tone-muted inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
    ]) }}>
        {{ $fallback }}
    </span>
@endif
