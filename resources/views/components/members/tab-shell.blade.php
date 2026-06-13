@props([
    'title',
])

<div {{ $attributes->class(['members-tab-panel']) }}>
    <div class="members-tab-panel-head">
        <h1 class="members-tab-title">{{ $title }}</h1>
        <x-members.logout-button />
    </div>

    {{ $slot }}
</div>
