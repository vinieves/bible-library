<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mi Biblioteca') — {{ $siteSettings['name'] ?? 'Biblioteca Bíblica Digital' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('scripts')
</head>
<body class="min-h-screen bg-bible-black pb-24 font-sans text-bible-cream antialiased md:pb-8">
    <x-members.header
        :show-back="$showBack ?? false"
        :page-title="($showBack ?? false) ? trim($__env->yieldContent('title')) : null"
    />

    @if(session('success'))
        <div class="mx-auto max-w-3xl px-4 sm:px-6 pt-4">
            <div class="rounded-xl border border-bible-green/40 bg-bible-green/20 px-4 py-3 text-base text-bible-cream sm:text-lg">
                {{ session('success') }}
            </div>
        </div>
    @endif

    <main class="mx-auto max-w-3xl px-4 py-5 sm:px-6 sm:py-6">
        @yield('content')
    </main>

    <x-members.bottom-nav />

    <div class="hidden md:block">
        <footer class="mx-auto max-w-3xl border-t border-bible-gold/20 px-4 py-6 text-center text-sm text-bible-cream/60 sm:px-6">
            <a href="mailto:{{ $siteSettings['support_email'] ?? '' }}" class="hover:text-bible-gold">
                ¿Necesita ayuda? Escríbanos
            </a>
        </footer>
    </div>
</body>
</html>
