@extends('layouts.members', ['showBack' => true])

@section('title', 'Progreso')

@section('content')
    <header class="mb-6">
        <h1 class="page-title mb-1.5">Su historial de actividad</h1>
        <p class="text-base text-bible-cream/55 sm:text-lg">Libros, videos y audios que ha iniciado o completado</p>
    </header>

    @if($activities->isEmpty())
        <div class="dashboard-continue-empty rounded-2xl px-4 py-8 text-center sm:px-6">
            <h2 class="text-lg font-semibold text-bible-gold">Sin actividad todavía</h2>
            <p class="mt-2 text-sm text-bible-cream/70">
                Cuando comience a leer, ver o escuchar contenido, su progreso aparecerá aquí.
            </p>
            <a href="{{ route('members.library') }}"
               class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-bible-gold transition hover:text-bible-gold/80">
                Ir a Libros
                <span aria-hidden="true">→</span>
            </a>
        </div>
    @else
        <div class="space-y-3">
            @foreach($activities as $activity)
                <a href="{{ $activity->url }}" class="dashboard-nav-card group">
                    <div class="dashboard-nav-icon m-3 bg-gradient-to-br from-bible-gold/20 to-bible-gold/5 text-bible-gold sm:m-3.5">
                        <span class="text-2xl sm:text-3xl">{{ $activity->icon() }}</span>
                    </div>

                    <div class="min-w-0 flex-1 py-3.5 pr-2 sm:py-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-bible-gold/10 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-bible-gold/80">
                                {{ $activity->typeLabel() }}
                            </span>
                            <span class="text-xs text-bible-cream/45">
                                {{ $activity->activityAt->locale('es')->translatedFormat('d M Y · H:i') }}
                            </span>
                        </div>
                        <h3 class="mt-1 text-base font-semibold leading-snug text-bible-gold sm:text-lg">{{ $activity->title }}</h3>
                        <p class="mt-0.5 line-clamp-2 text-sm leading-relaxed text-bible-cream/60">{{ $activity->subtitle }}</p>
                    </div>

                    <div class="mr-3 flex shrink-0 flex-col items-end justify-center gap-1 self-center sm:mr-4">
                        <span @class([
                            'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                            'bg-bible-green/20 text-bible-green' => $activity->completed || $activity->percent >= 100,
                            'bg-bible-gold/15 text-bible-gold' => ! $activity->completed && $activity->percent > 0,
                            'bg-bible-cream/10 text-bible-cream/60' => ! $activity->completed && $activity->percent <= 0,
                        ])>
                            {{ $activity->statusLabel() }}
                        </span>
                        <span class="text-bible-gold/40 transition group-hover:translate-x-0.5 group-hover:text-bible-gold">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
