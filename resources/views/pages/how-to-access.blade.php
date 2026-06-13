@extends('layouts.public')

@section('title', 'Cómo acceder')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-12">
        <h1 class="page-title mb-8">Cómo acceder a su biblioteca</h1>
        <div class="space-y-6 text-lg leading-relaxed text-bible-cream/90">
            <p>Después de adquirir el producto, recibirá un correo con sus datos de acceso a la Biblioteca Bíblica Digital.</p>
            <ol class="list-decimal space-y-4 pl-6">
                <li>Abra el correo de confirmación de compra.</li>
                <li>Use el enlace o vaya a <strong class="text-bible-gold">Iniciar sesión</strong> en esta página.</li>
                <li>Ingrese su correo y contraseña.</li>
                <li>Entrará directamente a su panel personal de estudio.</li>
            </ol>
            <p>Si no encuentra el correo, revise la carpeta de spam.</p>
            <a href="{{ route('login') }}" class="btn-primary mt-4 inline-flex">Iniciar sesión</a>
        </div>
    </div>
@endsection
