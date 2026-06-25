@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-line focus:border-brown focus:ring-brown/20 rounded-md shadow-sm']) }}>
