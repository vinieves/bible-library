@extends('layouts.public')

@section('title', 'Preguntas frecuentes')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-12">
        <h1 class="page-title mb-8">Preguntas frecuentes</h1>
        <div class="space-y-6">
            @foreach([
                ['¿Cómo entro a mi biblioteca?', 'Use su correo y contraseña en la página de inicio de sesión. Después del login verá su panel personal.'],
                ['¿Puedo estudiar desde el celular?', 'Sí. La biblioteca está optimizada para móvil, tablet y computadora.'],
                ['¿Cómo descargo los PDF?', 'Abra cualquier material al que tenga acceso y use el botón "Descargar PDF" si está disponible.'],
                ['¿Qué hago si un material está bloqueado?', 'Ese contenido requiere un plan superior. Use el botón "Desbloquear acceso" para ver la oferta.'],
                ['¿Necesito ayuda?', 'Escríbanos a ' . ($siteSettings['support_email'] ?? 'soporte@biblioteca.test') . ' y le responderemos pronto.'],
            ] as [$question, $answer])
                <div class="rounded-2xl border border-gold/20 bg-brown-deep p-6">
                    <h2 class="text-xl font-semibold text-gold">{{ $question }}</h2>
                    <p class="mt-3 text-lg text-cream/80">{{ $answer }}</p>
                </div>
            @endforeach
        </div>
    </div>
@endsection
