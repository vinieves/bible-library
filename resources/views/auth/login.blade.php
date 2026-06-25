@extends('layouts.public', ['hideHeader' => true, 'hideFooter' => true])

@section('title', 'Iniciar sesión')

@section('content')
    <div class="flex min-h-screen flex-col bg-paper lg:flex-row">
        {{-- Painel lateral (desktop) --}}
        <div class="relative hidden overflow-hidden bg-gradient-to-br from-brown/10 via-paper to-cream lg:flex lg:w-1/2 lg:flex-col lg:justify-between lg:p-12 xl:p-16">
            <div class="absolute -right-20 top-1/4 h-64 w-64 rounded-full bg-brown/10 blur-3xl" aria-hidden="true"></div>

            <div class="relative">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3 transition hover:opacity-90">
                    <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl border border-brown/20 shadow-lg shadow-brown/10">
                        <img src="{{ asset('images/logo.png') }}" alt="" class="h-full w-full object-cover">
                    </div>
                    <span class="font-display text-xl font-bold text-brown">
                        {{ $siteSettings['name'] ?? 'Biblioteca Bíblica Digital' }}
                    </span>
                </a>
            </div>

            <div class="relative max-w-md">
                <p class="inline-flex items-center gap-2 rounded-full border border-brown/25 bg-brown/10 px-4 py-1.5 font-ui text-xs font-semibold uppercase tracking-[0.2em] text-brown mb-6">
                    Acceso de miembros
                </p>
                <h2 class="font-display text-3xl font-bold leading-tight text-ink xl:text-4xl">
                    Su biblioteca bíblica le espera
                </h2>
                <p class="mt-4 font-ui text-lg leading-relaxed text-muted/80">
                    Ingrese con el correo electrónico vinculado a su compra para acceder a libros, videos, audios y estudios exclusivos.
                </p>
                <ul class="mt-8 space-y-3 font-ui text-sm text-muted/80">
                    <li class="flex items-center gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brown/10 text-brown">✓</span>
                        Explicaciones versículo por versículo
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brown/10 text-brown">✓</span>
                        Materiales PDF y progreso de lectura
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brown/10 text-brown">✓</span>
                        Acceso desde cualquier dispositivo
                    </li>
                </ul>
            </div>

            <p class="relative font-ui text-sm text-muted/60">
                {{ $siteSettings['footer_text'] ?? '' }}
            </p>
        </div>

        {{-- Formulário --}}
        <div class="flex flex-1 flex-col items-center justify-center px-4 py-10 sm:px-6 lg:py-16">
            {{-- Marca --}}
            <a href="{{ route('home') }}" class="mb-6 flex flex-col items-center gap-3 text-center lg:hidden">
                <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-3xl shadow-lg shadow-brown/10">
                    <img src="{{ asset('images/logo.png') }}" alt="" class="h-full w-full object-cover">
                </div>
                <span class="font-display text-2xl font-bold text-ink">
                    {{ $siteSettings['name'] ?? 'Biblioteca Bíblica Digital' }}
                </span>
                <span class="font-ui text-sm text-muted">
                    Use el correo electrónico de su compra
                </span>
            </a>

            <div class="w-full max-w-md">
                <div class="relative overflow-hidden rounded-2xl border border-line bg-cream p-6 shadow-sm sm:p-8">
                    @if(session('status'))
                        <div class="mb-6 rounded-xl border border-brown/30 bg-brown/10 px-4 py-3 text-center font-ui text-sm text-ink">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="mb-2 block font-ui text-sm font-medium text-muted">
                                Correo electrónico
                            </label>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-brown/50" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                </svg>
                                <input id="email"
                                       type="email"
                                       name="email"
                                       value="{{ old('email') }}"
                                       required
                                       autofocus
                                       autocomplete="email"
                                       placeholder="cliente@demo.com"
                                       class="w-full rounded-xl border border-line bg-beige py-4 pl-12 pr-4 font-ui text-base text-ink placeholder:text-muted transition focus:border-brown focus:bg-cream focus:outline-none focus:ring-2 focus:ring-brown/20">
                            </div>
                            @error('email')
                                <p class="mt-2 font-ui text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="btn-primary w-full font-ui !py-4">
                            <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                            Acceder a mi biblioteca
                        </button>
                    </form>

                    <div class="my-5 flex items-center gap-3" aria-hidden="true">
                        <div class="h-px flex-1 bg-line"></div>
                        <span class="font-ui text-xs uppercase tracking-wider text-muted">o</span>
                        <div class="h-px flex-1 bg-line"></div>
                    </div>

                    <a href="{{ route('pages.how-to-access') }}" class="btn-secondary w-full font-ui">
                        ¿Cómo acceder?
                    </a>

                    <div class="mt-6 text-center">
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-1 font-ui text-sm text-muted/60 transition hover:text-brown">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Volver al inicio
                        </a>
                    </div>
                </div>

                <p class="mt-8 text-center font-display text-sm italic text-muted">
                    «Lámpara es a mis pies tu palabra»
                    <span class="mt-1 block font-ui text-xs not-italic uppercase tracking-wider text-gold">— Salmos 119:105</span>
                </p>
            </div>
        </div>
    </div>
@endsection
