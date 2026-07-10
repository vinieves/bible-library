<div class="mm-rule-form-actions">
    <x-filament::button
        color="primary"
        icon="heroicon-o-check"
        size="sm"
        wire:click="saveRule"
    >
        Salvar regra
    </x-filament::button>

    <x-filament::button
        color="gray"
        size="sm"
        wire:click="cancelRuleForm"
    >
        Cancelar
    </x-filament::button>
</div>

<style>
    .mm-rule-form-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .mm-rule-form-actions .fi-btn {
        margin: 0;
    }
</style>
