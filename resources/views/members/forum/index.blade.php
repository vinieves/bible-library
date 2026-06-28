@extends('layouts.members', ['headerStyle' => 'tab'])

@section('title', 'Comunidad')

@push('scripts')
    @vite(['resources/js/audio-player.js', 'resources/js/forum.js'])
@endpush

@section('content')
    <img src="{{ asset('images/comunidade.png') }}"
         alt=""
         class="mb-4 h-36 w-full rounded-2xl object-cover sm:h-44"
         loading="eager">

    <div class="mb-4 rounded-xl border border-brown/30 bg-brown/10 px-4 py-3 text-base text-ink sm:text-lg">
        ¡Sigue interactuando! Después de 7 días interactuando también podrás hacer publicaciones en la comunidad.
    </div>

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
