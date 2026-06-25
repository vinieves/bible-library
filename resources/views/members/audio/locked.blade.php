@extends('layouts.members', ['showBack' => true])

@section('title', $audioTrack->title)

@section('content')
    <div class="rounded-2xl border border-gold/20 bg-brown-deep p-6 text-center sm:p-8">
        @if($audioTrack->coverUrl())
            <div class="mx-auto mb-5 aspect-square w-40 overflow-hidden rounded-xl opacity-80">
                <img src="{{ $audioTrack->coverUrl() }}" alt="" class="h-full w-full object-cover grayscale">
            </div>
        @else
            <div class="mx-auto mb-5 flex h-24 w-24 items-center justify-center rounded-full bg-gold/10 text-4xl">
                🔒
            </div>
        @endif

        <h1 class="text-xl font-semibold text-gold sm:text-2xl">{{ $audioTrack->title }}</h1>

        <p class="mx-auto mt-4 max-w-sm text-base leading-relaxed text-cream/80">
            Este audio forma parte de la {{ $subscriptionTitle }} Premium.
        </p>
        <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-cream/55">
            Desbloquee estudios narrados, devocionales y reflexiones para continuar aprendiendo de forma sencilla.
        </p>

        <a href="{{ $checkoutUrl }}"
           target="_blank"
           rel="noopener"
           class="btn-material-primary mt-6 inline-flex w-full sm:w-auto">
            Desbloquear audios premium
        </a>

        <a href="{{ route('members.audio.index') }}"
           class="btn-material-secondary mt-3 inline-flex w-full sm:w-auto">
            Volver a audios
        </a>
    </div>
@endsection
