@extends('layouts.public', ['hideHeader' => true])

@section('title', $siteSettings['name'] ?? 'Biblioteca Bíblica Digital')

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden px-4 pb-16 pt-24 sm:px-6 sm:pb-24 sm:pt-32 md:pt-36">
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -left-1/4 top-0 h-96 w-96 rounded-full bg-bible-gold/5 blur-3xl"></div>
            <div class="absolute -right-1/4 bottom-0 h-80 w-80 rounded-full bg-bible-green/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-4xl text-center">
            <div class="public-badge mb-8">
                <span class="h-1.5 w-1.5 rounded-full bg-bible-gold"></span>
                {{ $siteSettings['name'] ?? 'Biblioteca Bíblica Digital' }}
            </div>

            <h1 class="font-serif text-[1.75rem] font-bold leading-[1.2] tracking-tight text-bible-cream sm:text-4xl md:text-5xl lg:text-[3.25rem]">
                Toda la Biblia, explicada<br class="hidden sm:inline"> versículo por versículo
            </h1>

            <p class="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-bible-cream/70 sm:text-lg md:mt-8 md:text-xl">
                Su biblioteca bíblica digital con estudios claros, PDFs exclusivos y recursos organizados para profundizar en la Palabra de Dios a su ritmo.
            </p>

            <div class="mt-10 flex flex-col items-stretch justify-center gap-3 sm:flex-row sm:items-center sm:gap-4">
                @auth
                    <a href="{{ route('members.dashboard') }}" class="btn-primary w-full sm:w-auto sm:min-w-[240px]">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                        Acceder a mi biblioteca
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-primary w-full sm:w-auto sm:min-w-[240px]">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                        Acceder a mi biblioteca
                    </a>
                    <a href="{{ route('pages.how-to-access') }}" class="btn-secondary w-full sm:w-auto">
                        ¿Cómo acceder?
                    </a>
                @endauth
            </div>

            <p class="mt-8 text-xs text-bible-cream/40 sm:text-sm">
                Estudie en celular, tablet o computadora — donde esté
            </p>
        </div>
    </section>

    {{-- Features --}}
    <section class="mx-auto max-w-6xl px-4 pb-20 sm:px-6">
        <div class="mb-10 text-center">
            <h2 class="font-serif text-2xl font-bold text-bible-gold sm:text-3xl">Su Biblia digital, siempre con usted</h2>
            <p class="mt-2 text-bible-cream/60">Todo lo que necesita para estudiar la Escritura en profundidad</p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4 lg:gap-6">
            <div class="group public-feature-card">
                <div class="public-feature-icon">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-bible-gold">Todos los libros bíblicos</h3>
                <p class="mt-2 text-sm leading-relaxed text-bible-cream/65 sm:text-base">
                    Pentateuco, evangelios, salmos, cartas y más — organizados para un estudio guiado.
                </p>
            </div>

            <div class="group public-feature-card">
                <div class="public-feature-icon">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-bible-gold">Audios y contenidos de voz</h3>
                <p class="mt-2 text-sm leading-relaxed text-bible-cream/65 sm:text-base">
                    Escuche narraciones, reflexiones y estudios en audio — ideal para aprender en el camino o cuando prefiere escuchar.
                </p>
            </div>

            <div class="group public-feature-card">
                <div class="public-feature-icon">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-bible-gold">Materiales exclusivos</h3>
                <p class="mt-2 text-sm leading-relaxed text-bible-cream/65 sm:text-base">
                    Bonos, mapas mentales y devocionales que complementan su lectura bíblica.
                </p>
            </div>

            <div class="group public-feature-card">
                <div class="public-feature-icon">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-bible-gold">Donde usted esté</h3>
                <p class="mt-2 text-sm leading-relaxed text-bible-cream/65 sm:text-base">
                    Acceda desde el celular, la tablet o la computadora — su biblioteca siempre a mano.
                </p>
            </div>
        </div>
    </section>
@endsection
