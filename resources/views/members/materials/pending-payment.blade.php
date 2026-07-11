@extends('layouts.members', ['showBack' => true])

@section('title', 'Confirmando pago')

@section('content')
    <div
        class="py-8 text-center"
        @if($material)
            x-data="{
                unlocked: false,
                checking: true,
                async check() {
                    const response = await fetch('{{ route('members.materials.checkout.check-access', $material) }}', {
                        headers: { Accept: 'application/json' },
                    });
                    const data = await response.json();
                    if (data.unlocked) {
                        this.unlocked = true;
                        this.checking = false;
                        clearInterval(this.timer);
                    }
                },
                init() {
                    this.check();
                    this.timer = setInterval(() => this.check(), 4000);
                },
            }"
        @else
            x-data
        @endif
    >
        @if(! $material)
            <div class="mb-6 text-6xl">📧</div>
            <h2 class="page-title mb-4 text-center">Verifique su acceso</h2>
            <p class="mx-auto mb-8 max-w-md text-lg text-muted">
                No encontramos una compra pendiente en esta sesión. Si ya realizó el pago, su acceso se activa
                automáticamente en unos minutos — revise su correo de confirmación.
            </p>
            <a href="{{ route('members.materials.index') }}" class="btn-gold inline-flex">Volver a Materiales</a>
        @else
            <template x-if="checking">
                <div>
                    <div class="mb-6 flex justify-center">
                        <svg class="h-12 w-12 animate-spin text-gold" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                    <h2 class="page-title mb-4 text-center">Confirmando su pago…</h2>
                    <p class="mx-auto mb-8 max-w-md text-lg text-muted">
                        Estamos verificando su compra de <strong class="text-gold">{{ $material->title }}</strong>.
                        Esto puede tardar unos segundos.
                    </p>
                </div>
            </template>

            <template x-if="unlocked">
                <div>
                    <div class="mb-6 text-6xl">✅</div>
                    <h2 class="page-title mb-4 text-center">¡Listo!</h2>
                    <p class="mx-auto mb-8 max-w-md text-lg text-muted">
                        Su acceso a <strong class="text-gold">{{ $material->title }}</strong> fue activado.
                    </p>
                    <a href="{{ route('members.materials.show', $material) }}" class="btn-gold inline-flex">
                        Ver material
                    </a>
                </div>
            </template>
        @endif
    </div>
@endsection
