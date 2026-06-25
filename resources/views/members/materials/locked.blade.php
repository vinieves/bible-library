@extends('layouts.members', ['showBack' => true])

@section('title', 'Acceso bloqueado')

@section('content')
    <div class="py-8 text-center">
        <div class="mb-6 text-6xl">🔒</div>
        <h2 class="page-title mb-4 text-center">Este contenido requiere el Plan Completo</h2>
        <p class="mx-auto mb-8 max-w-md text-lg text-muted">
            <strong class="text-gold">{{ $material->title }}</strong> está disponible para clientes con
            <strong>Plan Completo</strong>.
        </p>

        @if($checkoutUrl)
            <a href="{{ $checkoutUrl }}" target="_blank" rel="noopener" class="btn-gold inline-flex">
                Obtener Plan Completo
            </a>
        @else
            <a href="mailto:{{ $siteSettings['support_email'] }}" class="btn-gold inline-flex">
                Contactar soporte
            </a>
        @endif

        <p class="mt-8 text-base text-muted">
            ¿Ya compró? Su acceso se activará automáticamente después de la confirmación.
        </p>
    </div>
@endsection
