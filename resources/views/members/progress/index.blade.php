@extends('layouts.members', ['showBack' => true])

@section('title', 'Mi progreso')

@section('content')
    <div class="mb-8 rounded-2xl border border-bible-gold/20 bg-bible-dark p-5">
        <div class="mb-2 flex justify-between text-lg">
            <span>Progreso total</span>
            <span class="font-bold text-bible-gold">{{ $progressPercent }}%</span>
        </div>
        <div class="h-5 overflow-hidden rounded-full bg-bible-black">
            <div class="h-full rounded-full bg-bible-green" style="width: {{ $progressPercent }}%"></div>
        </div>
        <p class="mt-3 text-base text-bible-cream/70">
            {{ $studiedCount }} materiales estudiados de {{ $totalPublished }}
        </p>
    </div>

    <section class="mb-10">
        <h2 class="section-title mb-4">Estudiados recientemente</h2>
        @forelse($studied as $item)
            <a href="{{ route('members.materials.show', $item->material) }}"
               class="member-card mb-3 flex items-center justify-between">
                <span class="text-lg">{{ $item->material->title }}</span>
                <span class="text-sm text-bible-cream/50">{{ $item->studied_at?->format('d/m/Y') }}</span>
            </a>
        @empty
            <p class="text-bible-cream/60">Aún no ha marcado materiales como estudiados.</p>
        @endforelse
    </section>
@endsection
