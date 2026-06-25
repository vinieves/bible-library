@extends('layouts.public')

@section('title', 'Recuperar contraseña')

@section('content')
    <div class="mx-auto max-w-md px-4 py-12">
        <h1 class="page-title mb-2 text-center">Recuperar contraseña</h1>
        <p class="mb-8 text-center text-lg text-cream/70">
            Le enviaremos un enlace para restablecer su contraseña
        </p>

        @if(session('status'))
            <div class="mb-4 rounded-xl border border-brown/40 bg-brown/20 px-4 py-3 text-cream">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5 rounded-2xl border border-gold/20 bg-brown-deep p-6">
            @csrf

            <div>
                <label for="email" class="mb-2 block text-lg font-medium text-cream">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="input-field">
                @error('email')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-primary w-full">
                Enviar enlace de recuperación
            </button>

            <div class="text-center">
                <a href="{{ route('login') }}" class="text-base text-gold hover:underline">
                    Volver al inicio de sesión
                </a>
            </div>
        </form>
    </div>
@endsection
