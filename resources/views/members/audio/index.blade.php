@extends('layouts.members', ['headerStyle' => 'tab'])

@section('title', 'Escuchar')

@section('content')
    <x-members.tab-shell title="Escuchar">
        <x-members.library-search
            :action="route('members.audio.index')"
            :search="$search"
            :categories="$categories"
            :category-id="$categoryId"
        />

        @if($tracks->isEmpty())
            @if($search || $categoryId)
                <p class="py-8 text-center text-sm text-bible-muted-warm">No se encontraron audios con esos filtros.</p>
            @else
                <x-members.empty-state
                    icon="audio"
                    title="Pronto, nuevos audios para su estudio"
                    message="Estamos preparando estudios y devocionales para que escuche donde quiera."
                />
            @endif
        @else
            <div class="audio-list space-y-3">
                @foreach($tracks as $track)
                    <x-members.audio-card
                        :track="$track"
                        :progress="$progressByTrack->get($track->id)"
                        :locked="! auth()->user()->hasAccessToAudioTrack($track)"
                    />
                @endforeach
            </div>
        @endif
    </x-members.tab-shell>
@endsection
