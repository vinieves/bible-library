@extends('layouts.members', ['showBack' => true])

@section('title', 'Bonos exclusivos')

@section('content')
    @if($bonuses->isEmpty())
        <p class="text-center text-lg text-bible-cream/70">No hay bonos disponibles por el momento.</p>
    @else
        <div class="space-y-3">
            @foreach($bonuses as $material)
                <x-members.bonus-card
                    :material="$material"
                    :progress="$progressByMaterial->get($material->id)"
                />
            @endforeach
        </div>
    @endif
@endsection
