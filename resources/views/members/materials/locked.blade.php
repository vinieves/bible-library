@extends('layouts.members', ['showBack' => true])

@section('title', 'Acceso bloqueado')

@section('content')
    <div x-data x-init="$dispatch('open-modal', 'upsell-{{ $material->id }}')" class="py-8 text-center">
        <div class="mb-6 text-6xl">🔒</div>
        <h2 class="page-title mb-4 text-center">Este contenido es exclusivo</h2>
        <p class="mx-auto mb-8 max-w-md text-lg text-muted">
            <strong class="text-gold">{{ $material->title }}</strong> requiere una compra adicional para desbloquearse.
        </p>

        <button
            type="button"
            class="btn-gold inline-flex"
            x-on:click="$dispatch('open-modal', 'upsell-{{ $material->id }}')"
        >
            Desbloquear
        </button>

        <p class="mt-8 text-base text-muted">
            ¿Ya compró? Su acceso se activará automáticamente después de la confirmación.
        </p>
    </div>

    <x-members.upsell-modal :material="$material" />
@endsection
