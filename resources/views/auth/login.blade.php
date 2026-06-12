@extends('layouts.public', ['hideHeader' => true, 'hideFooter' => true])

@section('title', 'Iniciar sesión')

@section('content')
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <h1 class="page-title mb-3 text-center">Entrar a mi biblioteca</h1>
            <p class="mb-10 text-center text-lg text-bible-cream/70">Ingrese su correo electrónico</p>

            @if(session('status'))
                <div class="mb-6 rounded-xl border border-bible-green/40 bg-bible-green/20 px-4 py-3 text-center text-bible-cream">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="email" class="sr-only">Correo electrónico</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                           autocomplete="email" placeholder="Correo electrónico"
                           class="input-field text-center">
                    @error('email')
                        <p class="mt-3 text-center text-base text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-primary w-full">
                    Entrar a mi biblioteca
                </button>
            </form>
        </div>
    </div>
@endsection
