@extends('layouts.public', ['hideHeader' => true])

@section('title', $siteSettings['name'] ?? 'Biblioteca Bíblica Digital')

@section('content')
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-bible-gold/10 to-transparent"></div>
        <div class="relative mx-auto max-w-4xl px-4 py-16 text-center md:py-24">
            <p class="mb-4 text-sm uppercase tracking-widest text-bible-gold">Estudio bíblico premium</p>
            <h1 class="font-serif text-3xl font-bold leading-tight text-bible-cream md:text-5xl">
                {{ $siteSettings['tagline'] ?? 'Todos los LIBROS DE LA BIBLIA explicados versículo por versículo' }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-bible-cream/80 md:text-xl">
                Acceda a su biblioteca digital con explicaciones claras, materiales exclusivos y estudios organizados para avanzar a su ritmo.
            </p>
            <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                @auth
                    <a href="{{ route('members.dashboard') }}" class="btn-primary w-full sm:w-auto">
                        Acceder a mi biblioteca
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-primary w-full sm:w-auto">
                        Acceder a mi biblioteca
                    </a>
                    <a href="{{ route('login') }}" class="btn-secondary w-full sm:w-auto">
                        Iniciar sesión
                    </a>
                @endauth
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-4xl px-4 py-12">
        <div class="grid gap-6 md:grid-cols-3">
            <div class="rounded-2xl border border-bible-gold/20 bg-bible-dark p-6 text-center">
                <div class="mb-3 text-4xl">📚</div>
                <h2 class="text-xl font-semibold text-bible-gold">Biblioteca completa</h2>
                <p class="mt-2 text-bible-cream/70">Libros, evangelios y estudios organizados por categoría.</p>
            </div>
            <div class="rounded-2xl border border-bible-gold/20 bg-bible-dark p-6 text-center">
                <div class="mb-3 text-4xl">✨</div>
                <h2 class="text-xl font-semibold text-bible-gold">Bonos exclusivos</h2>
                <p class="mt-2 text-bible-cream/70">Mapas mentales, devocionales y materiales premium.</p>
            </div>
            <div class="rounded-2xl border border-bible-gold/20 bg-bible-dark p-6 text-center">
                <div class="mb-3 text-4xl">📱</div>
                <h2 class="text-xl font-semibold text-bible-gold">Fácil de usar</h2>
                <p class="mt-2 text-bible-cream/70">Diseñado para estudiar en celular, tablet o computadora.</p>
            </div>
        </div>
    </section>
@endsection
