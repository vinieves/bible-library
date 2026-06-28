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
            <div x-data="{
                selectMode: false,
                selected: [],
                toggleSelected(id, url) {
                    const index = this.selected.findIndex((item) => item.id === id);

                    if (index === -1) {
                        this.selected.push({ id, url });
                    } else {
                        this.selected.splice(index, 1);
                    }
                },
                isSelected(id) {
                    return this.selected.some((item) => item.id === id);
                },
                printSelected() {
                    this.selected.forEach((item) => window.open(item.url, '_blank'));
                    this.selectMode = false;
                    this.selected = [];
                },
            }">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    @foreach($materials as $material)
                        <x-members.material-grid-card
                            :material="$material"
                            :progress="$progressByMaterial->get($material->id)"
                        />
                    @endforeach
                </div>

                <div class="mt-6 flex items-center justify-center">
                    <template x-if="!selectMode">
                        <button type="button" class="btn-material-secondary" @click="selectMode = true">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"/>
                            </svg>
                            Imprimir materiales
                        </button>
                    </template>

                    <template x-if="selectMode">
                        <div class="flex w-full max-w-sm items-center gap-2">
                            <div class="flex-1">
                                <button type="button" class="btn-material-ghost" @click="selectMode = false; selected = []">
                                    Cancelar
                                </button>
                            </div>
                            <div class="flex-1">
                                <button
                                    type="button"
                                    class="btn-material-primary"
                                    :disabled="selected.length === 0"
                                    :class="selected.length === 0 ? 'opacity-50' : ''"
                                    @click="printSelected()"
                                >
                                    <span x-text="selected.length ? `Imprimir (${selected.length})` : 'Imprimir'"></span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        @endif
    </x-members.tab-shell>
@endsection
