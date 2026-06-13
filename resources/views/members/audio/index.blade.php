@extends('layouts.members', ['showBack' => true])

@section('title', 'Escuchar')

@section('content')
    <header class="mb-4">
        <h1 class="page-title mb-1 text-xl sm:text-2xl">Biblioteca en Audio</h1>
        <p class="text-sm leading-relaxed text-bible-cream/60 sm:text-base">
            Estudios y devocionales incluidos en su Plan Completo.
        </p>
    </header>

    <section class="audio-highlight mb-5 px-4 py-3.5 sm:px-5 sm:py-4">
        <p class="text-[0.65rem] font-medium uppercase tracking-wider text-bible-gold/80">Plan Completo</p>
        <p class="mt-0.5 text-sm text-bible-cream/75">
            Escuche donde quiera — ideal para el camino, el descanso o la oración.
        </p>
    </section>

    <section>
        <h2 class="section-title mb-2.5 text-base sm:text-lg">Audios disponibles</h2>
        @if($tracks->isEmpty())
            <p class="text-sm text-bible-cream/60">Próximamente nuevos audios en la biblioteca.</p>
        @else
            <div class="audio-list space-y-2">
                @foreach($tracks as $track)
                    <x-members.audio-card
                        :track="$track"
                        :progress="$progressByTrack->get($track->id)"
                        :locked="! auth()->user()->hasAccessToAudioTrack($track)"
                    />
                @endforeach
            </div>
        @endif
    </section>
@endsection
