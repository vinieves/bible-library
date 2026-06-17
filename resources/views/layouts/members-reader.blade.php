<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — {{ $siteSettings['name'] ?? 'Biblioteca Bíblica Digital' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('scripts')
</head>
<body class="members-shell flex min-h-dvh flex-col bg-member-paper font-sans text-member-body antialiased">
    {{-- Header compacto --}}
    <header class="shrink-0 border-b border-member-gold/20 bg-member-card/95 backdrop-blur">
        <div class="flex min-h-[3.25rem] items-center justify-between gap-2 px-3 py-2 sm:px-4">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('members.dashboard') }}"
               class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-2 text-sm font-medium text-member-gold transition hover:bg-member-gold/10 sm:text-base">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                <span class="hidden sm:inline">Volver</span>
            </a>
            <p class="min-w-0 flex-1 truncate text-center text-sm font-medium text-member-title sm:text-base">
                @yield('title')
            </p>
            <x-members.logout-button />
        </div>
    </header>

    {{-- Área principal: PDF ocupa todo o espaço disponível --}}
    <main class="flex min-h-0 flex-1 flex-col pb-20 md:pb-4">
        @yield('content')
    </main>

    <x-members.bottom-nav />
</body>
</html>
