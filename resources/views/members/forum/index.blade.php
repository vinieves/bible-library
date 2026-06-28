@extends('layouts.members', ['headerStyle' => 'tab'])

@section('title', 'Comunidad')

@push('scripts')
    @vite(['resources/js/audio-player.js', 'resources/js/forum.js'])
@endpush

@section('content')
    <x-members.tab-shell title="Comunidad">
        @if($posts->isEmpty())
            <x-members.empty-state
                icon="forum"
                title="Aún no hay publicaciones"
                message="Pronto el equipo compartirá novedades y reflexiones aquí."
            />
        @else
            <div class="space-y-4">
                @foreach($posts as $post)
                    <x-members.forum-post-card
                        :post="$post"
                        :reacted="$reactedPostIds->has($post->id)"
                    />
                @endforeach
            </div>
        @endif
    </x-members.tab-shell>
@endsection
