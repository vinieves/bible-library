@extends('layouts.members')

@section('title', 'Inicio')

@section('content')
    <header class="mb-6">
        <h1 class="page-title mb-1.5">Bienvenido a su Biblioteca Bíblica</h1>
        <p class="text-base text-bible-cream/55 sm:text-lg">Elija qué desea estudiar hoy</p>
    </header>

    {{-- Progreso --}}
    <section class="dashboard-progress mb-6">
        <div class="mb-3 flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-bible-cream/40">Su progreso</p>
                <p class="mt-0.5 text-sm text-bible-cream/65">Materiales completados</p>
            </div>
            <div class="dashboard-progress-ring">
                <span class="text-base font-bold text-bible-gold">{{ $progressPercent }}%</span>
            </div>
        </div>

        <x-members.progress-bar
            :percent="$progressPercent"
            :show-label="false"
            class="[&_.progress-bar-track]:h-2"
        />

        <p class="mt-3 text-xs text-bible-cream/50">
            {{ $studiedCount }} de {{ $totalPublished }} materiales estudiados
        </p>
    </section>

    {{-- Acesso rápido --}}
    <section>
        <p class="mb-3 text-xs font-medium uppercase tracking-wider text-bible-cream/40">Acceso rápido</p>
        <div class="space-y-3">
            @if($recentMaterial)
                @php
                    $continueHref = $recentMaterial->hasPdf()
                        ? route('members.materials.pdf.reader', $recentMaterial)
                        : route('members.materials.show', $recentMaterial);
                    $continueSubtitle = $recentMaterial->title;
                    if ($recentProgress && ! $recentProgress->is_studied && $recentProgress->last_page_read > 0) {
                        $continueSubtitle = $recentMaterial->title.' · '.$recentProgress->statusLabel($recentMaterial);
                    }
                @endphp
                <x-members.card
                    :href="$continueHref"
                    title="Continuar estudiando"
                    :subtitle="$continueSubtitle"
                    accent="green"
                    :material="$recentMaterial"
                />
            @endif

            <x-members.card
                href="{{ route('members.audio.index') }}"
                title="Escuchar audios"
                subtitle="Estudios bíblicos y devocionales para escuchar desde su celular."
                icon="🎧"
                accent="gold"
            />
        </div>
    </section>
@endsection
