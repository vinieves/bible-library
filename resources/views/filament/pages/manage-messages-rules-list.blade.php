<div class="mm-rules">
    @forelse ($rules as $rule)
        @php
            /** @var \App\Models\WhatsAppMessageTemplate $rule */
            $event = $rule->event;
            $preview = \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', trim($rule->body)), 90);
        @endphp

        <div wire:key="rule-{{ $event->value }}" class="mm-rule-card fi-section">
            <div class="fi-section-content-ctn">
                <div class="mm-rule-card__inner fi-section-content">
                    <div class="mm-rule-card__main">
                        <div class="mm-rule-card__title-row">
                            <h4 class="mm-rule-card__title">
                                {{ $event->conditionLabel() }}
                            </h4>

                            <x-filament::badge
                                :color="$rule->is_enabled ? 'success' : 'gray'"
                                size="sm"
                            >
                                {{ $rule->is_enabled ? 'Ativa' : 'Inativa' }}
                            </x-filament::badge>
                        </div>

                        <div class="mm-rule-card__meta">
                            <x-filament::badge color="gray" size="sm">
                                {{ $event->hotmartEvent() }}
                            </x-filament::badge>

                            <span class="mm-rule-card__action">
                                {{ $event->systemAction() }}
                            </span>
                        </div>

                        @if (filled($preview))
                            <p class="mm-rule-card__preview">
                                {{ $preview }}
                            </p>
                        @endif
                    </div>

                    <div class="mm-rule-card__controls">
                        <button
                            type="button"
                            wire:click="toggleRule('{{ $event->value }}')"
                            role="switch"
                            aria-checked="{{ $rule->is_enabled ? 'true' : 'false' }}"
                            aria-label="{{ $rule->is_enabled ? 'Desativar regra' : 'Ativar regra' }}"
                            @class([
                                'fi-toggle',
                                'fi-toggle-on' => $rule->is_enabled,
                                'fi-toggle-off' => ! $rule->is_enabled,
                            ])
                        >
                            <div>
                                <div aria-hidden="true"></div>
                                <div aria-hidden="true"></div>
                            </div>
                        </button>

                        <x-filament::icon-button
                            color="gray"
                            icon="heroicon-o-pencil-square"
                            label="Editar regra"
                            wire:click="openEditRuleForm('{{ $event->value }}')"
                        />

                        <x-filament::icon-button
                            color="danger"
                            icon="heroicon-o-trash"
                            label="Excluir regra"
                            wire:click="deleteRule('{{ $event->value }}')"
                            wire:confirm="Excluir esta regra de mensagem?"
                        />
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="mm-rules-empty fi-section">
            <div class="fi-section-content-ctn">
                <div class="mm-rules-empty__inner fi-section-content">
                    <div class="mm-rules-empty__icon" aria-hidden="true">
                        <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="h-6 w-6" />
                    </div>

                    <div>
                        <p class="mm-rules-empty__title">
                            Nenhuma regra de mensagem criada
                        </p>
                        <p class="mm-rules-empty__text">
                            Crie uma regra escolhendo o status da venda Hotmart e definindo a mensagem em espanhol.
                        </p>
                    </div>

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
        </div>
    @endforelse
</div>

<style>
    .mm-rules {
        display: grid;
        gap: 0.75rem;
    }

    .mm-rule-card.fi-section,
    .mm-rules-empty.fi-section {
        margin: 0;
    }

    .mm-rule-card__inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .mm-rule-card__main {
        min-width: 0;
        flex: 1;
    }

    .mm-rule-card__title-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.35rem;
    }

    .mm-rule-card__title {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 600;
        line-height: 1.35;
        color: rgb(250 250 250);
    }

    .mm-rule-card__meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.35rem;
    }

    .mm-rule-card__action {
        font-size: 0.75rem;
        color: rgb(161 161 170);
    }

    .mm-rule-card__preview {
        margin: 0;
        font-size: 0.8rem;
        line-height: 1.45;
        color: rgb(161 161 170);
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .mm-rule-card__controls {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        gap: 0.35rem;
    }

    .mm-rule-card__controls .fi-toggle.fi-toggle-on {
        background-color: rgb(56 189 248) !important;
        border-color: rgb(56 189 248) !important;
    }

    .mm-rule-card__controls .fi-toggle.fi-toggle-on > :first-child {
        background-color: rgb(255 255 255);
    }

    .mm-rule-card__controls .fi-toggle.fi-toggle-off {
        background-color: rgb(63 63 70);
        border-color: rgb(63 63 70);
    }

    .mm-rules-empty__inner {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding-block: 1.5rem;
        text-align: center;
    }

    .mm-rules-empty__icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 9999px;
        background: rgb(39 39 42);
        color: rgb(161 161 170);
    }

    .mm-rules-empty__title {
        margin: 0 0 0.25rem;
        font-size: 0.95rem;
        font-weight: 600;
        color: rgb(250 250 250);
    }

    .mm-rules-empty__text {
        margin: 0;
        max-width: 26rem;
        font-size: 0.8rem;
        line-height: 1.5;
        color: rgb(161 161 170);
    }

    @media (max-width: 640px) {
        .mm-rule-card__inner {
            flex-direction: column;
            align-items: flex-start;
        }

        .mm-rule-card__controls {
            width: 100%;
            justify-content: flex-end;
        }
    }
</style>
