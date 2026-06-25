@props([
    'title',
])

<div {{ $attributes->class(['members-tab-page']) }}>
    <div class="members-tab-head">
        <h1 class="members-tab-title">{{ $title }}</h1>
    </div>

    {{ $slot }}
</div>
