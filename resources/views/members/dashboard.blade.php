@extends('layouts.members')

@section('title', 'Inicio')

@section('content')
    <header class="mb-6">
        <h1 class="page-title mb-1.5">Bienvenido a su Biblioteca Bíblica</h1>
        <p class="text-base text-bible-cream/55 sm:text-lg">Elija qué desea estudiar hoy</p>
    </header>

    <section class="dashboard-progress mb-6">
        <p class="text-xs font-medium uppercase tracking-wider text-bible-cream/40">Su progreso</p>

        @if($lastActivityAt)
            <p class="mt-1.5 text-sm text-bible-cream/65">
                Última actividad: {{ $lastActivityAt->locale('es')->diffForHumans() }}
            </p>
        @else
            <p class="mt-1.5 text-sm text-bible-cream/65">Aún no tiene actividad registrada.</p>
        @endif

        <a href="{{ route('members.progress') }}"
           class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-bible-gold transition hover:text-bible-gold/80">
            Ver historial completo
            <span aria-hidden="true">→</span>
        </a>
    </section>

    <section>
        <p class="mb-3 text-xs font-medium uppercase tracking-wider text-bible-cream/40">Continuar consumiendo</p>

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
                <p class="text-sm text-bible-cream/70">
                    Comience explorando <span class="font-medium text-bible-gold">{{ $suggestedStartLabel }}</span>.
                </p>
                <a href="{{ $suggestedStartUrl }}"
                   class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-bible-gold transition hover:text-bible-gold/80">
                    Ir a Libros
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        @endif
    </section>
@endsection
