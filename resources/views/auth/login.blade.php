@extends('layouts.public', ['hideHeader' => true, 'hideFooter' => true])

@section('title', 'Iniciar sesión')

@section('content')
    <div class="flex min-h-screen flex-col lg:flex-row">
        {{-- Painel lateral (desktop) --}}
        <div class="relative hidden overflow-hidden bg-gradient-to-br from-bible-green/30 via-bible-dark to-bible-black lg:flex lg:w-1/2 lg:flex-col lg:justify-between lg:p-12 xl:p-16">
            <div class="absolute inset-0 bg-public-mesh opacity-60" aria-hidden="true"></div>
            <div class="absolute -right-20 top-1/4 h-64 w-64 rounded-full bg-bible-gold/10 blur-3xl" aria-hidden="true"></div>

            <div class="relative">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3 transition hover:opacity-90">
                    <div class="public-brand-icon">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <span class="font-serif text-xl font-bold text-bible-gold">
                        {{ $siteSettings['name'] ?? 'Biblioteca Bíblica Digital' }}
                    </span>
                </a>
            </div>

            <div class="relative max-w-md">
                <p class="public-badge mb-6">Acceso de miembros</p>
                <h2 class="font-serif text-3xl font-bold leading-tight text-bible-cream xl:text-4xl">
                    Su biblioteca bíblica le espera
                </h2>
                <p class="mt-4 text-lg leading-relaxed text-bible-cream/70">
                    Ingrese con el correo electrónico vinculado a su compra para acceder a libros, bonos y estudios exclusivos.
                </p>
                <ul class="mt-8 space-y-3 text-sm text-bible-cream/60">
                    <li class="flex items-center gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-bible-green/20 text-bible-gold">✓</span>
                        Explicaciones versículo por versículo
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-bible-green/20 text-bible-gold">✓</span>
                        Materiales PDF y progreso de lectura
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-bible-green/20 text-bible-gold">✓</span>
                        Acceso desde cualquier dispositivo
                    </li>
                </ul>
            </div>

            <p class="relative text-sm text-bible-cream/40">
                {{ $siteSettings['footer_text'] ?? '' }}
            </p>
        </div>

        {{-- Formulário --}}
        <div class="flex flex-1 flex-col items-center justify-center px-4 py-10 sm:px-6 lg:py-16">
            {{-- Logo mobile --}}
            <a href="{{ route('home') }}" class="mb-8 flex items-center gap-3 lg:hidden">
                <div class="public-brand-icon !h-11 !w-11">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <span class="font-serif text-lg font-bold text-bible-gold">
                    {{ $siteSettings['name'] ?? 'Biblioteca Bíblica Digital' }}
                </span>
            </a>

            <div class="w-full max-w-md">
                <div class="public-login-panel">
                    <div class="mb-8 text-center lg:text-left">
                        <h1 class="font-serif text-2xl font-bold text-bible-gold sm:text-3xl">
                            Entrar a mi biblioteca
                        </h1>
                        <p class="mt-2 text-bible-cream/65">
                            Use el correo electrónico de su compra
                        </p>
                    </div>

                    @if(session('status'))
                        <div class="mb-6 rounded-xl border border-bible-green/30 bg-bible-green/15 px-4 py-3 text-center text-sm text-bible-cream">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-bible-cream/80">
                                Correo electrónico
                            </label>
                            <div class="public-input-wrap">
                                <svg class="public-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                </svg>
                                <input id="email"
                                       type="email"
                                       name="email"
                                       value="{{ old('email') }}"
                                       required
                                       autofocus
                                       autocomplete="email"
                                       placeholder="su@correo.com"
                                       class="public-input-field">
                            </div>
                            @error('email')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="btn-primary w-full !py-4">
                            <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                            Entrar a mi biblioteca
                        </button>
                    </form>

                    <div class="mt-8 space-y-3 border-t border-bible-gold/10 pt-6 text-center text-sm">
                        <p class="text-bible-cream/50">
                            ¿Aún no tiene acceso?
                            <a href="{{ route('pages.how-to-access') }}" class="font-medium text-bible-gold hover:underline">
                                Ver cómo acceder
                            </a>
                        </p>
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-1 text-bible-cream/40 transition hover:text-bible-gold">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Volver al inicio
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
