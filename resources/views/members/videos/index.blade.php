@extends('layouts.members', ['showBack' => true])

@section('title', 'Videos')

@section('content')
    <header class="mb-4">
        <h1 class="page-title mb-1 text-xl sm:text-2xl">Biblioteca Bíblica en Video</h1>
        <p class="text-sm leading-relaxed text-bible-cream/60 sm:text-base">
            Estudios y enseñanzas en video incluidos en su Plan Completo.
        </p>
    </header>

    <section>
        <h2 class="section-title mb-2.5 text-base sm:text-lg">Videos disponibles</h2>
        @if($videos->isEmpty())
            <p class="text-sm text-bible-cream/60">Próximamente nuevos videos en la biblioteca.</p>
        @else
            <div class="video-list space-y-2">
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
