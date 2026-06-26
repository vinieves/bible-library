@extends('layouts.members', ['headerStyle' => 'tab'])

@section('title', 'Materiales')

@section('content')
    <x-members.tab-shell title="Materiales">
        @if($materials->isEmpty())
            <x-members.empty-state
                icon="video"
                title="Aún no hay materiales"
                message="Pronto encontrará aquí libros, devocionales y mapas mentales para descargar."
            />
        @else
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                @foreach($materials as $material)
                    <x-members.material-grid-card
                        :material="$material"
                        :progress="$progressByMaterial->get($material->id)"
                    />
                @endforeach
            </div>
        @endif
    </x-members.tab-shell>
@endsection
