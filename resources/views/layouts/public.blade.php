<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $siteSettings['name'] ?? 'Biblioteca Bíblica Digital')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-bible-black font-sans text-bible-cream antialiased">
    @unless($hideHeader ?? false)
        <header class="border-b border-bible-gold/20 bg-bible-dark">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-5">
                <a href="{{ route('home') }}" class="font-serif text-xl font-bold text-bible-gold md:text-2xl">
                    {{ $siteSettings['name'] ?? 'Biblioteca Bíblica Digital' }}
                </a>
                <div class="flex gap-3">
                    @auth
                        <a href="{{ route('members.dashboard') }}" class="btn-primary !py-3 !px-5 !text-base">Mi biblioteca</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-secondary !py-3 !px-5 !text-base">Iniciar sesión</a>
                    @endauth
                </div>
            </div>
        </header>
    @endunless

    <main>
        @yield('content')
    </main>

    @unless($hideFooter ?? false)
        <footer class="mt-16 border-t border-bible-gold/20 bg-bible-dark py-8">
            <div class="mx-auto max-w-5xl px-4 text-center text-bible-cream/70">
                <p class="mb-4 text-lg">{{ $siteSettings['footer_text'] ?? '' }}</p>
                <div class="flex flex-wrap justify-center gap-6 text-base">
                    <a href="{{ route('pages.how-to-access') }}" class="hover:text-bible-gold">Cómo acceder</a>
                    <a href="{{ route('pages.faq') }}" class="hover:text-bible-gold">Preguntas frecuentes</a>
                    <a href="mailto:{{ $siteSettings['support_email'] ?? '' }}" class="hover:text-bible-gold">Soporte</a>
                </div>
            </div>
        </footer>
    @endunless
</body>
</html>
