<div class="space-y-3">
    @forelse ($rules as $rule)
        @php
            /** @var \App\Models\WhatsAppMessageTemplate $rule */
            $event = $rule->event;
        @endphp

        <div
            wire:key="rule-{{ $event->value }}"
            class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-white/10 dark:bg-gray-900/50"
        >
            <div class="min-w-0 flex-1 space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h4 class="text-sm font-semibold text-gray-950 dark:text-white">
                        {{ $event->label() }}
                    </h4>

                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 font-mono text-xs text-gray-600 dark:bg-white/10 dark:text-gray-300">
                        {{ $event->hotmartEvent() }}
                    </span>

                    @if ($rule->is_enabled)
                        <span class="inline-flex items-center rounded-full bg-success-500/10 px-2 py-0.5 text-xs font-medium text-success-600 dark:text-success-400">
                            Ativa
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-gray-500/10 px-2 py-0.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                            Inativa
                        </span>
                    @endif
                </div>

                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $event->systemAction() }}
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-3 sm:justify-end">
                <label class="relative inline-flex cursor-pointer items-center" title="{{ $rule->is_enabled ? 'Desativar regra' : 'Ativar regra' }}">
                    <input
                        type="checkbox"
                        class="peer sr-only"
                        @checked($rule->is_enabled)
                        wire:click.prevent="toggleRule('{{ $event->value }}')"
                    >
                    <div @class([
                        'h-6 w-11 rounded-full bg-gray-200 transition',
                        'after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[\'\']',
                        'peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white',
                        'dark:bg-gray-700 dark:after:border-gray-600 dark:peer-checked:bg-primary-500',
                    ])></div>
                </label>

                <x-filament::icon-button
                    color="gray"
                    icon="heroicon-o-pencil-square"
                    label="Editar regra"
                    wire:click="openEditRuleForm('{{ $event->value }}')"
                />
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-gray-300 px-6 py-10 text-center dark:border-white/10">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Nenhuma regra criada ainda.
            </p>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                Clique em <strong>Criar regra</strong> para configurar a primeira mensagem automática.
            </p>

            <div class="mt-4">
                <x-filament::button
                    color="primary"
                    icon="heroicon-o-plus"
                    size="sm"
                    wire:click="openCreateRuleForm"
                >
                    Criar regra
                </x-filament::button>
            </div>
        </div>
    @endforelse
</div>
