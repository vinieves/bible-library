@extends('layouts.members', ['showBack' => true])

@section('title', 'Videos')

@section('content')
    <header class="mb-5">
        <h1 class="page-title mb-1.5 text-2xl sm:text-3xl">Biblioteca Bíblica en Video</h1>
        <p class="text-base leading-relaxed text-bible-cream/65 sm:text-lg">
            Estudios y enseñanzas en video incluidos en su Plan Completo.
        </p>
    </header>

    <section class="video-highlight mb-6">
        <p class="text-xs font-medium uppercase tracking-wider text-bible-gold/80">Plan Completo</p>
        <h2 class="mt-1 text-lg font-semibold text-bible-cream sm:text-xl">Estudios bíblicos en video</h2>
        <p class="mt-2 text-sm leading-relaxed text-bible-cream/65 sm:text-base">
            Aprenda con contenido visual, claro y organizado por categorías.
        </p>
    </section>

    <section>
        <h2 class="section-title mb-3 text-lg">Videos disponibles</h2>
        @if($videos->isEmpty())
            <p class="text-sm text-bible-cream/60">Próximamente nuevos videos en la biblioteca.</p>
        @else
            <div class="space-y-3">
                @foreach($videos as $video)
                    @php
                        $hasAccess = auth()->user()->hasAccessToVideo($video);
                    @endphp
                    <x-members.video-card
                        :video="$video"
                        :progress="$progressByVideo->get($video->id)"
                        :locked="! $hasAccess"
                    />
                @endforeach
            </div>
        @endif
    </section>
@endsection
