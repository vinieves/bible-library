@extends('layouts.members', ['headerStyle' => 'tab'])

@section('title', 'Videos')

@section('content')
    <x-members.tab-shell title="Videos">
        <x-members.library-search
            :action="route('members.videos.index')"
            :search="$search"
            :categories="$categories"
            :category-id="$categoryId"
            class="mb-5"
        />

        <section>
            @if($videos->isEmpty())
                @if($search || $categoryId)
                    <p class="py-6 text-center text-sm text-bible-muted-warm">No se encontraron videos con esos filtros.</p>
                @else
                    <x-members.empty-state
                        icon="video"
                        title="Pronto, nuevos videos para su estudio"
                        message="Estamos preparando enseñanzas en video para profundizar en la Palabra."
                    />
                @endif
            @else
                <div class="video-list space-y-3">
                    @foreach($videos as $video)
                        <x-members.video-card
                            :video="$video"
                            :progress="$progressByVideo->get($video->id)"
                            :locked="! auth()->user()->hasAccessToVideo($video)"
                        />
                    @endforeach
                </div>
            @endif
        </section>
    </x-members.tab-shell>
@endsection
