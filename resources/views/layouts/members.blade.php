<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapid-public-key" content="{{ \App\Models\Setting::get('vapid_public_key') }}">
    <title>@yield('title', 'Mi Biblioteca') — {{ $siteSettings['name'] ?? 'Biblioteca Bíblica Digital' }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('icons/icon-192.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#2F2117">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Biblia digital">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('scripts')
</head>
<body class="members-shell min-h-screen bg-paper pb-24 font-sans text-muted antialiased">
    @php
        $headerStyle = $headerStyle ?? (($showBack ?? false) ? 'back' : 'home');
    @endphp

    @if($headerStyle !== 'tab')
        <x-members.header
            :show-back="$showBack ?? false"
            :page-title="in_array($headerStyle, ['back'], true) ? trim($__env->yieldContent('title')) : null"
        />
    @endif

    @if(session('success'))
        <div class="mx-auto max-w-3xl px-4 sm:px-6 pt-4">
            <div class="rounded-xl border border-brown/30 bg-brown/10 px-4 py-3 text-base text-ink sm:text-lg">
                {{ session('success') }}
            </div>
        </div>
    @endif

    <main @class([
        'mx-auto max-w-3xl',
        'px-4 py-4 sm:px-6 sm:py-5' => ($headerStyle ?? 'home') === 'tab',
        'px-4 py-5 sm:px-6 sm:py-6' => ($headerStyle ?? 'home') !== 'tab',
    ])>
        @yield('content')
    </main>

    <x-members.bottom-nav />

    <div class="hidden md:block">
        <footer class="mx-auto max-w-3xl border-t border-brown/20 px-4 py-6 text-center text-sm text-muted sm:px-6">
            <a href="mailto:{{ $siteSettings['support_email'] ?? '' }}" class="hover:text-brown">
                ¿Necesita ayuda? Escríbanos
            </a>
        </footer>
    </div>
</body>
</html>
