@extends('layouts.members', ['showBack' => true])

@section('title', $video->title)

@push('scripts')
    @vite(['resources/js/video-player.js'])
@endpush

@section('content')
    <div class="video-player-page"
         data-video-player
         data-stream-url="{{ $video->hasVideoFile() ? route('members.videos.stream', $video) : '' }}"
         data-save-url="{{ route('members.videos.progress', $video) }}"
         data-initial-seconds="{{ $progress->progress_seconds }}"
         data-duration-seconds="{{ $video->durationSeconds() ?? 0 }}">

        @if($video->hasVideoFile())
            <div class="video-player-stage">
                <div class="video-player-shell">
                <video data-video-element
                       class="video-player-media"
                       src="{{ route('members.videos.stream', $video) }}"
                       playsinline
                       webkit-playsinline
                       x-webkit-airplay="allow"
                       preload="auto"
                       @if($video->coverUrl()) poster="{{ $video->coverUrl() }}" @endif></video>

                <div class="video-player-overlay" data-video-overlay>
                    <button type="button" data-video-play-center class="btn-video-play-center" aria-label="Reproducir">
                        <svg data-icon-play class="h-10 w-10" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                        <svg data-icon-pause class="hidden h-10 w-10" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                        </svg>
                    </button>
                </div>

                <div class="video-player-controls">
                    <div class="mb-3">
                        <div class="progress-bar-track h-2 cursor-pointer" data-video-progress-track>
                            <div data-video-progress-fill class="progress-bar-fill-partial h-full rounded-full transition-all" style="width: 0%"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <button type="button" data-video-play class="btn-video-control" aria-label="Reproducir o pausar">
                                <svg data-icon-play-sm class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                                <svg data-icon-pause-sm class="hidden h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                                </svg>
                            </button>
                            <button type="button" data-video-back class="btn-video-control" aria-label="Retroceder 10 segundos">-10s</button>
                            <button type="button" data-video-forward class="btn-video-control" aria-label="Avanzar 10 segundos">+10s</button>
                        </div>

                        <div class="flex items-center gap-2 text-xs text-bible-cream/70">
                            <span data-video-current>0:00</span>
                            <span>/</span>
                            <span data-video-duration>{{ $video->duration ?? '0:00' }}</span>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        @else
            <div class="mt-2 rounded-2xl border border-bible-gold/15 bg-bible-dark/60 p-5 text-center">
                <p class="text-sm text-bible-cream/60">
                    El archivo de video aún no está disponible. Vuelva pronto o contacte a soporte.
                </p>
            </div>
        @endif

        <div class="video-player-meta">
            <h1 class="video-player-title">{{ $video->title }}</h1>
            @if($video->category)
                <p class="mt-1 text-sm text-bible-cream/50">{{ $video->category->name }}</p>
            @endif
            @if($video->description)
                <p class="mt-3 text-sm leading-relaxed text-bible-cream/70">
                    {{ $video->description }}
                </p>
            @endif

            <div class="video-player-actions">
                <form method="POST" action="{{ route('members.videos.complete', $video) }}">
                    @csrf
                    <button type="submit"
                            class="btn-material-ghost w-full {{ $progress->completed ? 'btn-material-ghost-active' : '' }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ $progress->completed ? 'Visto' : 'Marcar como visto' }}
                    </button>
                </form>

                <a href="{{ route('members.videos.index') }}" class="btn-material-secondary flex w-full">
                    Volver a videos
                </a>
            </div>
        </div>
    </div>
@endsection
