@props([
    'post',
    'reacted' => false,
])

@php
    $persona = $post->persona;
    $photoUrl = $persona->photoUrl();
    $youtubeEmbedUrl = $post->youtubeEmbedUrl();
    $imageUrls = $post->imageUrls();
@endphp

<article class="overflow-hidden rounded-2xl border border-line bg-paper shadow-sm">
    <div class="flex items-center gap-3 p-4 pb-0 sm:p-5 sm:pb-0">
        <div class="h-10 w-10 shrink-0 overflow-hidden rounded-full bg-brown/10">
            @if($photoUrl)
                <img src="{{ $photoUrl }}" alt="{{ $persona->name }}" class="h-full w-full object-cover">
            @else
                <div class="flex h-full w-full items-center justify-center text-sm font-semibold text-brown">
                    {{ Str::upper(Str::substr($persona->name, 0, 1)) }}
                </div>
            @endif
        </div>
        <div class="min-w-0">
            <p class="truncate font-ui text-sm font-semibold text-ink">{{ $persona->name }}</p>
            <p class="text-xs text-muted">{{ $post->created_at->diffForHumans() }}</p>
        </div>
    </div>

    <div class="p-4 sm:p-5">
        @if($youtubeEmbedUrl)
            <div class="mt-3 aspect-[16/9] w-full overflow-hidden rounded-xl bg-ink">
                <iframe
                    class="h-full w-full"
                    src="{{ $youtubeEmbedUrl }}"
                    title="{{ $post->title ?? 'Video' }}"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                    loading="lazy"
                ></iframe>
            </div>
        @elseif(count($imageUrls) > 0)
            <div class="relative mt-3" x-data="{ active: 0, lightbox: false }">
                <div class="aspect-[4/5] w-full overflow-hidden rounded-xl bg-cream">
                    @foreach($imageUrls as $i => $url)
                        <img
                            x-show="active === {{ $i }}"
                            @click="lightbox = true"
                            src="{{ $url }}"
                            alt=""
                            class="h-full w-full cursor-zoom-in object-cover"
                            loading="lazy"
                        >
                    @endforeach
                </div>

                @if(count($imageUrls) > 1)
                    <button
                        type="button"
                        @click="active = (active - 1 + {{ count($imageUrls) }}) % {{ count($imageUrls) }}"
                        class="absolute left-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-ink/50 text-cream"
                        aria-label="Imagen anterior"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="active = (active + 1) % {{ count($imageUrls) }}"
                        class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-ink/50 text-cream"
                        aria-label="Imagen siguiente"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <div class="absolute bottom-2 left-1/2 flex -translate-x-1/2 gap-1.5">
                        @foreach($imageUrls as $i => $url)
                            <button
                                type="button"
                                @click="active = {{ $i }}"
                                :class="active === {{ $i }} ? 'bg-gold' : 'bg-cream/60'"
                                class="h-1.5 w-1.5 rounded-full"
                                aria-label="Ir a la imagen {{ $i + 1 }}"
                            ></button>
                        @endforeach
                    </div>
                @endif

                <div
                    x-show="lightbox"
                    x-cloak
                    @click="lightbox = false"
                    @keydown.escape.window="lightbox = false"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-ink/90 p-4"
                >
                    <button
                        type="button"
                        @click.stop="lightbox = false"
                        class="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full bg-cream/10 text-cream hover:bg-cream/20"
                        aria-label="Cerrar"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    @foreach($imageUrls as $i => $url)
                        <img
                            x-show="active === {{ $i }}"
                            @click.stop
                            src="{{ $url }}"
                            alt=""
                            class="max-h-full max-w-full rounded-lg object-contain"
                        >
                    @endforeach
                </div>
            </div>
        @endif

        @if($post->hasAudioFile())
            <div class="audio-player-page mt-3" data-audio-player data-stream-url="{{ route('members.forum.audio', $post) }}">
                <div class="audio-player-controls">
                    <div class="mb-3 flex items-center justify-center gap-4">
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

                    <div class="flex items-center gap-3 text-xs text-muted">
                        <span data-audio-current>0:00</span>
                        <div class="progress-bar-track h-2 min-w-0 flex-1">
                            <div data-audio-progress-fill class="progress-bar-fill-partial h-full rounded-full transition-all" style="width: 0%"></div>
                        </div>
                        <span data-audio-duration>0:00</span>
                    </div>

                    <audio data-audio-element class="hidden" preload="metadata"></audio>
                </div>
            </div>
        @endif

        @if($post->title)
            <h2 class="mt-3 text-lg font-semibold leading-snug text-ink sm:text-xl">{{ $post->title }}</h2>
        @endif

        <div class="prose prose-invert mt-2 max-w-none text-base leading-relaxed text-muted">
            {!! $post->body !!}
        </div>

        <div class="mt-4 flex items-center gap-3 border-t border-line pt-4">
            <button
                type="button"
                data-forum-reaction
                data-post-id="{{ $post->id }}"
                data-react-url="{{ route('members.forum.react', $post) }}"
                data-reacted="{{ $reacted ? '1' : '0' }}"
                aria-pressed="{{ $reacted ? 'true' : 'false' }}"
                @class([
                    'flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium transition',
                    'border-gold/40 bg-gold/10 text-gold' => $reacted,
                    'border-line text-muted hover:text-brown' => ! $reacted,
                ])
            >
                <span aria-hidden="true">🙏</span>
                <span>Amén</span>
                <span class="text-xs text-muted" data-forum-reaction-count>{{ $post->totalReactionsCount() }}</span>
            </button>
        </div>
    </div>
</article>
