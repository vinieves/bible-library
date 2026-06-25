@extends('layouts.members', ['showBack' => true])

@section('title', 'Progreso')

@section('content')
    <header class="mb-6">
        <h1 class="page-title mb-1.5">Su historial de actividad</h1>
        <p class="text-base text-muted sm:text-lg">Libros, videos y audios que ha iniciado o completado</p>
    </header>

    @if($activities->isEmpty())
        <div class="dashboard-continue-empty rounded-2xl px-4 py-8 text-center sm:px-6">
            <h2 class="text-lg font-semibold text-gold">Sin actividad todavía</h2>
            <p class="mt-2 text-sm text-muted">
                Cuando comience a leer, ver o escuchar contenido, su progreso aparecerá aquí.
            </p>
            <a href="{{ route('members.library') }}"
               class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-brown px-5 py-2.5 text-sm font-semibold text-cream shadow-sm shadow-brown/20 transition hover:bg-ink active:scale-[0.98]">
                Ir a Buscador
                <span aria-hidden="true">→</span>
            </a>
        </div>
    @else
        <div class="space-y-3">
            @foreach($activities as $activity)
                <a href="{{ $activity->url }}" class="dashboard-nav-card group">
                    <div class="dashboard-nav-icon m-3 bg-gradient-to-br from-gold/20 to-gold/5 text-gold sm:m-3.5">
                        <span class="text-2xl sm:text-3xl">{{ $activity->icon() }}</span>
                    </div>

                    <div class="min-w-0 flex-1 py-3.5 pr-2 sm:py-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-gold/10 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-gold/80">
                                {{ $activity->typeLabel() }}
                            </span>
                            <span class="text-xs text-muted">
                                {{ $activity->activityAt->locale('es')->translatedFormat('d M Y · H:i') }}
                            </span>
                        </div>
                        <h3 class="mt-1 text-base font-semibold leading-snug text-gold sm:text-lg">{{ $activity->title }}</h3>
                        <p class="mt-0.5 line-clamp-2 text-sm leading-relaxed text-muted">{{ $activity->subtitle }}</p>
                    </div>

                    <div class="mr-3 flex shrink-0 flex-col items-end justify-center gap-1 self-center sm:mr-4">
                        <span @class([
                            'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                            'bg-brown/20 text-brown' => $activity->completed || $activity->percent >= 100,
                            'bg-gold/15 text-gold' => ! $activity->completed && $activity->percent > 0,
                            'bg-line text-muted' => ! $activity->completed && $activity->percent <= 0,
                        ])>
                            {{ $activity->statusLabel() }}
                        </span>
                        <span class="text-gold/40 transition group-hover:translate-x-0.5 group-hover:text-gold">
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
