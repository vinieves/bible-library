@extends('layouts.members')

@section('title', 'Inicio')

@section('content')
    <div class="mb-4">
        <x-members.pwa-install />
    </div>

    <x-members.dashboard-hero />

    <div class="mt-5 space-y-4">
        <header class="dashboard-welcome">
            <h1 class="dashboard-welcome-greeting mb-1.5">Bienvenido</h1>
            <p class="dashboard-welcome-subtitle">Tu camino para comprender toda la Biblia comienza aquí.</p>
        </header>

        <x-members.verse-of-the-day :verse="$verseOfTheDay" />

        <x-members.monthly-goal-progress :goal="$monthlyGoal" />

        <section>
            <p class="mb-3 text-xs font-medium uppercase tracking-wider text-muted/65">Comience donde lo dejó</p>

            @if(count($continueCards) > 0)
                <div class="space-y-3">
                    @foreach($continueCards as $card)
                        <x-members.card
                            :href="$card['href']"
                            :title="$card['title']"
                            :subtitle="$card['subtitle']"
                            :icon="$card['icon']"
                            :accent="$card['accent']"
                            :material="$card['material'] ?? null"
                        />
                    @endforeach
                </div>
            @else
                <div class="dashboard-continue-empty rounded-2xl px-4 py-5 text-center sm:px-6">
                    <p class="text-sm text-muted">
                        Aún no tiene progreso guardado. Comience explorando
                        <span class="font-medium text-brown">{{ $suggestedStartLabel }}</span>.
                    </p>
                    <a href="{{ $suggestedStartUrl }}"
                       class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-brown px-5 py-2.5 text-sm font-semibold text-cream shadow-sm shadow-brown/20 transition hover:bg-ink active:scale-[0.98]">
                        Ir a Buscador
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            @endif
        </section>
    </div>
@endsection
