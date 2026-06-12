@extends('layouts.public')

@section('title', 'Nueva contraseña')

@section('content')
    <div class="mx-auto max-w-md px-4 py-12">
        <h1 class="page-title mb-8 text-center">Crear nueva contraseña</h1>

        <form method="POST" action="{{ route('password.store') }}" class="space-y-5 rounded-2xl border border-bible-gold/20 bg-bible-dark p-6">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <label for="email" class="mb-2 block text-lg text-bible-cream">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required class="input-field">
                @error('email')<p class="mt-2 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="mb-2 block text-lg text-bible-cream">Nueva contraseña</label>
                <input id="password" type="password" name="password" required class="input-field">
                @error('password')<p class="mt-2 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-2 block text-lg text-bible-cream">Confirmar contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required class="input-field">
            </div>

            <button type="submit" class="btn-primary w-full">Guardar contraseña</button>
        </form>
    </div>
@endsection
