@extends('layouts.members', ['showBack' => true])

@section('title', $audioTrack->title)

@push('scripts')
    @vite(['resources/js/audio-player.js'])
@endpush

@section('content')
    <div class="audio-player-page"
         data-audio-player
         data-stream-url="{{ $audioTrack->hasAudioFile() ? route('members.audio.stream', $audioTrack) : '' }}"
         data-save-url="{{ route('members.audio.progress', $audioTrack) }}"
         data-initial-seconds="{{ $progress->progress_seconds }}"
         data-duration-seconds="{{ $audioTrack->durationSeconds() ?? 0 }}">

        @if($audioTrack->coverUrl())
            <div class="mx-auto mb-5 aspect-square w-full max-w-xs overflow-hidden rounded-2xl border border-bible-gold/20">
                <img src="{{ $audioTrack->coverUrl() }}"
                     alt="{{ $audioTrack->title }}"
                     class="h-full w-full object-cover">
            </div>
        @endif

        <h1 class="text-center text-xl font-semibold text-bible-gold sm:text-2xl">{{ $audioTrack->title }}</h1>
        @if($audioTrack->category)
            <p class="mt-1 text-center text-sm text-bible-cream/50">{{ $audioTrack->category->name }}</p>
        @endif
        @if($audioTrack->description)
            <p class="mx-auto mt-3 max-w-md text-center text-sm leading-relaxed text-bible-cream/70">
                {{ $audioTrack->description }}
            </p>
        @endif

        @if($audioTrack->hasAudioFile())
            <div class="audio-player-controls mt-6">
                <div class="mb-4 flex items-center justify-center gap-4">
                    <button type="button" data-audio-back class="btn-audio-control" aria-label="Retroceder 15 segundos">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.334 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/>
                        </svg>
                        <span class="text-xs">15s</span>
                    </button>

                    <button type="button" data-audio-play class="btn-audio-play" aria-label="Reproducir o pausar">
                        <svg data-icon-play class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                        <svg data-icon-pause class="hidden h-8 w-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                        </svg>
                    </button>

                    <button type="button" data-audio-forward class="btn-audio-control" aria-label="Avanzar 15 segundos">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z"/>
                        </svg>
                        <span class="text-xs">15s</span>
                    </button>
                </div>

                <div class="flex items-center gap-3 text-xs text-bible-cream/60">
                    <span data-audio-current>0:00</span>
                    <div class="progress-bar-track h-2 min-w-0 flex-1">
                        <div data-audio-progress-fill class="progress-bar-fill-partial h-full rounded-full transition-all" style="width: 0%"></div>
                    </div>
                    <span data-audio-duration>{{ $audioTrack->duration ?? '0:00' }}</span>
                </div>

                <audio data-audio-element class="hidden" preload="metadata"></audio>
            </div>
        @else
            <div class="mt-6 rounded-2xl border border-bible-gold/15 bg-bible-dark/60 p-5 text-center">
                <p class="text-sm text-bible-cream/60">
                    El archivo de audio aún no está disponible. Vuelva pronto o contacte a soporte.
                </p>
            </div>
        @endif

        <div class="mt-6 space-y-3">
            <form method="POST" action="{{ route('members.audio.complete', $audioTrack) }}">
                @csrf
                <button type="submit"
                        class="btn-material-ghost w-full {{ $progress->completed ? 'btn-material-ghost-active' : '' }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ $progress->completed ? 'Escuchado' : 'Marcar como escuchado' }}
                </button>
            </form>

            <a href="{{ route('members.audio.index') }}" class="btn-material-secondary flex w-full">
                Volver a audios
            </a>
        </div>
    </div>
@endsection
