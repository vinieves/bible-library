@extends('layouts.members', ['showBack' => true])

@section('title', $video->title)

@section('content')
    <div class="rounded-2xl border border-gold/20 bg-brown-deep p-6 text-center sm:p-8">
        @if($video->coverUrl())
            <div class="mx-auto mb-5 aspect-video w-full max-w-sm overflow-hidden rounded-xl opacity-80">
                <img src="{{ $video->coverUrl() }}" alt="" class="h-full w-full object-cover grayscale">
            </div>
        @else
            <div class="mx-auto mb-5 flex h-24 w-24 items-center justify-center rounded-full bg-gold/10 text-4xl">
                🔒
            </div>
        @endif

        <h1 class="text-xl font-semibold text-gold sm:text-2xl">{{ $video->title }}</h1>

        <p class="mx-auto mt-4 max-w-sm text-base leading-relaxed text-cream/80">
            Este video forma parte del {{ $subscriptionTitle }} Premium.
        </p>
        <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-cream/55">
            Desbloquee estudios en video y continúe aprendiendo con contenido exclusivo.
        </p>

        <a href="{{ $checkoutUrl }}"
           target="_blank"
           rel="noopener"
           class="btn-material-primary mt-6 inline-flex w-full sm:w-auto">
            Desbloquear acceso
        </a>

        <a href="{{ route('members.videos.index') }}"
           class="btn-material-secondary mt-3 inline-flex w-full sm:w-auto">
            Volver a videos
        </a>
    </div>
@endsection
