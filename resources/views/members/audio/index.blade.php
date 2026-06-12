@extends('layouts.members', ['showBack' => true])

@section('title', 'Escuchar')

@section('content')
    <header class="mb-5">
        <h1 class="page-title mb-1.5 text-2xl sm:text-3xl">Biblioteca Bíblica en Audio</h1>
        <p class="text-base leading-relaxed text-bible-cream/65 sm:text-lg">
            Escuche estudios bíblicos, devocionales y reflexiones para fortalecer su fe.
        </p>
    </header>

    {{-- Destaque --}}
    <section class="audio-highlight mb-6">
        <p class="text-xs font-medium uppercase tracking-wider text-bible-gold/80">Nuevo</p>
        <h2 class="mt-1 text-lg font-semibold text-bible-cream sm:text-xl">Estudios bíblicos en audio</h2>
        <p class="mt-2 text-sm leading-relaxed text-bible-cream/65 sm:text-base">
            Ideal para aprender mientras descansa, camina o realiza sus actividades.
        </p>
        <a href="#audios-premium" class="btn-material-primary mt-4 inline-flex w-full sm:w-auto">
            Conocer audios premium
        </a>
    </section>

    {{-- Oferta premium --}}
    <section id="audios-premium" class="audio-offer mb-8">
        <h2 class="text-lg font-semibold text-bible-gold sm:text-xl">{{ $subscriptionTitle }} Premium</h2>
        <p class="mt-2 text-sm leading-relaxed text-bible-cream/70 sm:text-base">
            Acceda a estudios narrados, devocionales en audio, oraciones guiadas y nuevas reflexiones cada mes.
        </p>
        <ul class="mt-4 space-y-2 text-sm text-bible-cream/75">
            <li class="flex items-start gap-2">
                <span class="mt-0.5 text-bible-green">✓</span>
                Estudios bíblicos narrados
            </li>
            <li class="flex items-start gap-2">
                <span class="mt-0.5 text-bible-green">✓</span>
                Devocionales en audio
            </li>
            <li class="flex items-start gap-2">
                <span class="mt-0.5 text-bible-green">✓</span>
                Oraciones guiadas
            </li>
            <li class="flex items-start gap-2">
                <span class="mt-0.5 text-bible-green">✓</span>
                Nuevos audios cada mes
            </li>
            <li class="flex items-start gap-2">
                <span class="mt-0.5 text-bible-green">✓</span>
                Ideal para escuchar desde el celular
            </li>
        </ul>
        @if($subscriptionPrice)
            <p class="mt-4 text-sm font-medium text-bible-gold">{{ $subscriptionPrice }}</p>
        @endif
        <a href="{{ $checkoutUrl }}"
           target="_blank"
           rel="noopener"
           class="btn-material-primary mt-4 inline-flex w-full sm:w-auto">
            Desbloquear audios premium
        </a>
    </section>

    {{-- Gratuitos --}}
    <section class="mb-8">
        <h2 class="section-title mb-3 text-lg">Audios gratuitos</h2>
        @if($freeTracks->isEmpty())
            <p class="text-sm text-bible-cream/60">No hay audios gratuitos disponibles por el momento.</p>
        @else
            <div class="space-y-3">
                @foreach($freeTracks as $track)
                    <x-members.audio-card
                        :track="$track"
                        :progress="$progressByTrack->get($track->id)"
                    />
                @endforeach
            </div>
        @endif
    </section>

    {{-- Premium --}}
    <section>
        <h2 class="section-title mb-3 text-lg">Audios premium</h2>
        @if($premiumTracks->isEmpty())
            <p class="text-sm text-bible-cream/60">Próximamente nuevos audios premium.</p>
        @else
            <div class="space-y-3">
                @foreach($premiumTracks as $track)
                    @php
                        $hasAccess = auth()->user()->hasAccessToAudioTrack($track);
                    @endphp
                    <x-members.audio-card
                        :track="$track"
                        :progress="$progressByTrack->get($track->id)"
                        :locked="! $hasAccess"
                    />
                @endforeach
            </div>
        @endif
    </section>
@endsection
