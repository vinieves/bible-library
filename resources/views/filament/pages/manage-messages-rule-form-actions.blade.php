<div class="flex flex-wrap items-center gap-3">
    <x-filament::button
        color="primary"
        icon="heroicon-o-check"
        wire:click="saveRule"
    >
        Salvar regra
    </x-filament::button>

    <x-filament::button
        color="gray"
        wire:click="cancelRuleForm"
    >
        Cancelar
    </x-filament::button>
</div>
