@extends('layouts.members', ['showBack' => true])

@section('title', 'Acceso bloqueado')

@section('content')
    <div class="py-8 text-center">
        <div class="mb-6 text-6xl">🔒</div>
        <h2 class="page-title mb-4 text-center">Este material forma parte de la Biblioteca Premium</h2>
        <p class="mx-auto mb-8 max-w-md text-lg text-bible-cream/80">
            <strong class="text-bible-gold">{{ $material->title }}</strong> requiere el plan
            <strong>{{ $material->plan?->name }}</strong> o superior.
        </p>

        @if($checkoutUrl)
            <a href="{{ $checkoutUrl }}" target="_blank" rel="noopener" class="btn-gold inline-flex">
                Desbloquear acceso
            </a>
        @else
            <a href="mailto:{{ $siteSettings['support_email'] }}" class="btn-gold inline-flex">
                Contactar soporte
            </a>
        @endif

        <p class="mt-8 text-base text-bible-cream/50">
            ¿Ya compró? Su acceso se activará automáticamente después de la confirmación.
        </p>
    </div>
@endsection
