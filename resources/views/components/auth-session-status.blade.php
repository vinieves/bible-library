@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-brown']) }}>
        {{ $status }}
    </div>
@endif
