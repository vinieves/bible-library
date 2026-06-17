@extends('layouts.members')

@section('title', 'Inicio')

@section('content')
    <header class="mb-6">
        <h1 class="page-title mb-1.5">Bienvenido a su Biblioteca Bíblica</h1>
        <p class="text-base text-member-body/80 sm:text-lg">Elija qué desea estudiar hoy</p>
    </header>

    <section>
        <p class="mb-3 text-xs font-medium uppercase tracking-wider text-member-body/65">Comience donde lo dejó</p>

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
                <p class="text-sm text-member-body">
                    Aún no tiene progreso guardado. Comience explorando
                    <span class="font-medium text-member-gold">{{ $suggestedStartLabel }}</span>.
                </p>
                <a href="{{ $suggestedStartUrl }}"
                   class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-member-gold transition hover:text-member-gold-dark">
                    Ir a Libros
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        @endif
    </section>
@endsection
