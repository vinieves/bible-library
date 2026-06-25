<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $siteSettings['tagline'] ?? 'Biblioteca Bíblica Digital — estudios bíblicos versículo por versículo' }}">
    <title>@yield('title', $siteSettings['name'] ?? 'Biblioteca Bíblica Digital')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('icons/icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|playfair-display:600,700,800|lora:600,700|dm-sans:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-shell font-sans text-cream antialiased">
    @unless($hideHeader ?? false)
        <header class="sticky top-0 z-50 border-b border-gold/10 bg-ink/90 backdrop-blur-md">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
                <a href="{{ route('home') }}" class="flex items-center gap-3 transition hover:opacity-90">
                    <div class="public-brand-icon !h-10 !w-10">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <span class="font-serif text-lg font-bold text-gold sm:text-xl">
                        {{ $siteSettings['name'] ?? 'Biblioteca Bíblica Digital' }}
                    </span>
                </a>
                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('members.dashboard') }}" class="btn-primary !py-2.5 !px-5 !text-sm sm:!text-base">
                            Mi biblioteca
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn-secondary !py-2.5 !px-5 !text-sm sm:!text-base">
                            Iniciar sesión
                        </a>
                    @endauth
                </div>
            </div>
        </header>
    @endunless

    <main>
        @yield('content')
    </main>

    @unless($hideFooter ?? false)
        <footer class="border-t border-gold/10 bg-ink/80 py-10 backdrop-blur-sm">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="flex flex-col items-center gap-6 text-center">
                    <div class="public-brand-icon !h-11 !w-11">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <p class="max-w-md text-sm text-cream/60 sm:text-base">
                        {{ $siteSettings['footer_text'] ?? '' }}
                    </p>
                    <nav class="flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm sm:text-base">
                        <a href="{{ route('pages.how-to-access') }}" class="public-footer-link">Cómo acceder</a>
                        <a href="{{ route('pages.faq') }}" class="public-footer-link">Preguntas frecuentes</a>
                        <a href="mailto:{{ $siteSettings['support_email'] ?? '' }}" class="public-footer-link">Soporte</a>
                    </nav>
                </div>
            </div>
        </footer>
    @endunless
</body>
</html>
