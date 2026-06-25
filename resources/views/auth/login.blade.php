@extends('layouts.public', ['hideHeader' => true, 'hideFooter' => true])

@section('title', 'Iniciar sesión')

@section('content')
    <div class="flex min-h-screen flex-col bg-member-paper lg:flex-row">
        {{-- Painel lateral (desktop) --}}
        <div class="relative hidden overflow-hidden bg-gradient-to-br from-member-gold/10 via-member-paper to-member-card lg:flex lg:w-1/2 lg:flex-col lg:justify-between lg:p-12 xl:p-16">
            <div class="absolute -right-20 top-1/4 h-64 w-64 rounded-full bg-member-gold/10 blur-3xl" aria-hidden="true"></div>

            <div class="relative">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3 transition hover:opacity-90">
                    <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl border border-member-gold/20 shadow-lg shadow-member-gold/10">
                        <img src="{{ asset('images/logo.png') }}" alt="" class="h-full w-full object-cover">
                    </div>
                    <span class="font-serif text-xl font-bold text-member-gold">
                        {{ $siteSettings['name'] ?? 'Biblioteca Bíblica Digital' }}
                    </span>
                </a>
            </div>

            <div class="relative max-w-md">
                <p class="inline-flex items-center gap-2 rounded-full border border-member-gold/25 bg-member-gold/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-member-gold mb-6">
                    Acceso de miembros
                </p>
                <h2 class="font-serif text-3xl font-bold leading-tight text-member-title xl:text-4xl">
                    Su biblioteca bíblica le espera
                </h2>
                <p class="mt-4 text-lg leading-relaxed text-member-body/80">
                    Ingrese con el correo electrónico vinculado a su compra para acceder a libros, videos, audios y estudios exclusivos.
                </p>
                <ul class="mt-8 space-y-3 text-sm text-member-body/80">
                    <li class="flex items-center gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-member-gold/10 text-member-gold">✓</span>
                        Explicaciones versículo por versículo
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-member-gold/10 text-member-gold">✓</span>
                        Materiales PDF y progreso de lectura
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-member-gold/10 text-member-gold">✓</span>
                        Acceso desde cualquier dispositivo
                    </li>
                </ul>
            </div>

            <p class="relative text-sm text-member-body/60">
                {{ $siteSettings['footer_text'] ?? '' }}
            </p>
        </div>

        {{-- Formulário --}}
        <div class="flex flex-1 flex-col items-center justify-center px-4 py-10 sm:px-6 lg:py-16">
            {{-- Logo mobile --}}
            <a href="{{ route('home') }}" class="mb-8 flex items-center gap-3 lg:hidden">
                <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-2xl border border-member-gold/20 shadow-lg shadow-member-gold/10">
                    <img src="{{ asset('images/logo.png') }}" alt="" class="h-full w-full object-cover">
                </div>
                <span class="font-serif text-lg font-bold text-member-gold">
                    {{ $siteSettings['name'] ?? 'Biblioteca Bíblica Digital' }}
                </span>
            </a>

            <div class="w-full max-w-md">
                <div class="relative overflow-hidden rounded-3xl border border-member-gold/20 bg-member-card p-8 shadow-xl shadow-member-gold/10 sm:p-10">
                    <div class="mb-8 text-center lg:text-left">
                        <h1 class="font-serif text-2xl font-bold text-member-gold sm:text-3xl">
                            Entrar a mi biblioteca
                        </h1>
                        <p class="mt-2 text-member-body/80">
                            Use el correo electrónico de su compra
                        </p>
                    </div>

                    @if(session('status'))
                        <div class="mb-6 rounded-xl border border-member-gold/30 bg-member-gold/10 px-4 py-3 text-center text-sm text-member-title">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-member-body/90">
                                Correo electrónico
                            </label>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-member-gold/50" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
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
                                       class="w-full rounded-xl border-2 border-member-gold/25 bg-member-input py-4 pl-12 pr-4 text-base text-member-title placeholder:text-member-placeholder transition focus:border-member-gold focus:bg-white focus:outline-none focus:ring-2 focus:ring-member-gold/20">
                            </div>
                            @error('email')
                                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="btn-primary w-full !py-4">
                            <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                            Entrar a mi biblioteca
                        </button>
                    </form>

                    <div class="mt-8 space-y-3 border-t border-member-gold/15 pt-6 text-center text-sm">
                        <p class="text-member-body/70">
                            ¿Aún no tiene acceso?
                            <a href="{{ route('pages.how-to-access') }}" class="font-medium text-member-gold hover:underline">
                                Ver cómo acceder
                            </a>
                        </p>
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-1 text-member-body/60 transition hover:text-member-gold">
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
