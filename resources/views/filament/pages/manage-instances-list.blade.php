<div class="mm-instances">
    @if (! $apiReady)
        <div class="mm-instances-empty fi-section">
            <div class="fi-section-content-ctn">
                <div class="mm-instances-empty__inner fi-section-content">
                    <div class="mm-instances-empty__icon" aria-hidden="true">
                        <x-filament::icon icon="heroicon-o-link" class="h-6 w-6" />
                    </div>

                    <div>
                        <p class="mm-instances-empty__title">
                            Evolution API não configurada
                        </p>
                        <p class="mm-instances-empty__text">
                            Informe a URL base e a API Key em <strong>Integrações API</strong> para gerenciar instâncias WhatsApp.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @else
        @forelse ($instances as $instance)
            @php
                $isActive = $activeInstance === $instance['name'];
                $isConnected = in_array(strtolower($instance['state']), ['open'], true);
                $needsConnect = in_array(strtolower($instance['state']), ['close', 'closed', 'connecting', 'unknown'], true);
            @endphp

            <div wire:key="instance-{{ $instance['name'] }}" class="mm-instance-card fi-section">
                <div class="fi-section-content-ctn">
                    <div class="mm-instance-card__inner fi-section-content">
                        <div class="mm-instance-card__main">
                            <div class="mm-instance-card__title-row">
                                <h4 class="mm-instance-card__title">
                                    {{ $instance['name'] }}
                                </h4>

                                <x-filament::badge
                                    :color="$instance['stateColor']"
                                    size="sm"
                                >
                                    {{ $instance['stateLabel'] }}
                                </x-filament::badge>

                                @if ($isActive)
                                    <x-filament::badge color="info" size="sm">
                                        Instância ativa
                                    </x-filament::badge>
                                @endif
                            </div>

                            <div class="mm-instance-card__meta">
                                @if (filled($instance['profileName'] ?? null))
                                    <span class="mm-instance-card__meta-item">
                                        Perfil: {{ $instance['profileName'] }}
                                    </span>
                                @endif

                                @if (filled($instance['ownerJid'] ?? null))
                                    <span class="mm-instance-card__meta-item">
                                        {{ preg_replace('/@.*/', '', $instance['ownerJid']) }}
                                    </span>
                                @endif

                                @if (filled($instance['instanceId'] ?? null))
                                    <span class="mm-instance-card__meta-item mm-instance-card__meta-item--muted">
                                        ID: {{ \Illuminate\Support\Str::limit($instance['instanceId'], 18) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="mm-instance-card__controls">
                            @if (! $isActive && $isConnected)
                                <x-filament::button
                                    color="primary"
                                    size="xs"
                                    wire:click="setActiveInstance('{{ $instance['name'] }}')"
                                >
                                    Usar esta
                                </x-filament::button>
                            @endif

                            @if ($needsConnect)
                                <x-filament::button
                                    color="success"
                                    size="xs"
                                    icon="heroicon-o-qr-code"
                                    wire:click="connectInstance('{{ $instance['name'] }}')"
                                >
                                    Conectar
                                </x-filament::button>
                            @endif

                            @if ($isConnected)
                                <x-filament::icon-button
                                    color="warning"
                                    icon="heroicon-o-arrow-path"
                                    label="Reiniciar"
                                    wire:click="restartInstance('{{ $instance['name'] }}')"
                                    wire:confirm="Reiniciar a instância {{ $instance['name'] }}?"
                                />

                                <x-filament::icon-button
                                    color="gray"
                                    icon="heroicon-o-arrow-right-on-rectangle"
                                    label="Desconectar"
                                    wire:click="logoutInstance('{{ $instance['name'] }}')"
                                    wire:confirm="Desconectar WhatsApp da instância {{ $instance['name'] }}?"
                                />
                            @endif

                            <x-filament::icon-button
                                color="danger"
                                icon="heroicon-o-trash"
                                label="Excluir instância"
                                wire:click="deleteInstance('{{ $instance['name'] }}')"
                                wire:confirm="Excluir permanentemente a instância {{ $instance['name'] }}? Esta ação não pode ser desfeita."
                            />
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="mm-instances-empty fi-section">
                <div class="fi-section-content-ctn">
                    <div class="mm-instances-empty__inner fi-section-content">
                        <div class="mm-instances-empty__icon" aria-hidden="true">
                            <x-filament::icon icon="heroicon-o-device-phone-mobile" class="h-6 w-6" />
                        </div>

                        <div>
                            <p class="mm-instances-empty__title">
                                Nenhuma instância encontrada
                            </p>
                            <p class="mm-instances-empty__text">
                                Clique em <strong>Nova instância</strong> para criar e conectar um WhatsApp.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endforelse
    @endif
</div>

<style>
    .mm-instances {
        display: grid;
        gap: 0.75rem;
    }

    .mm-instance-card.fi-section,
    .mm-instances-empty.fi-section {
        margin: 0;
    }

    .mm-instance-card__inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .mm-instance-card__main {
        min-width: 0;
        flex: 1;
    }

    .mm-instance-card__title-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.35rem;
    }

    .mm-instance-card__title {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 600;
        line-height: 1.35;
        color: rgb(250 250 250);
    }

    .mm-instance-card__meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }

    .mm-instance-card__meta-item {
        font-size: 0.75rem;
        color: rgb(212 212 216);
    }

    .mm-instance-card__meta-item--muted {
        color: rgb(161 161 170);
    }

    .mm-instance-card__controls {
        display: flex;
        flex-shrink: 0;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 0.35rem;
    }

    .mm-instances-empty__inner {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding-block: 1.5rem;
        text-align: center;
    }

    .mm-instances-empty__icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 9999px;
        background: rgb(39 39 42);
        color: rgb(161 161 170);
    }

    .mm-instances-empty__title {
        margin: 0 0 0.25rem;
        font-size: 0.95rem;
        font-weight: 600;
        color: rgb(250 250 250);
    }

    .mm-instances-empty__text {
        margin: 0;
        max-width: 26rem;
        font-size: 0.8rem;
        line-height: 1.5;
        color: rgb(161 161 170);
    }

    @media (max-width: 640px) {
        .mm-instance-card__inner {
            flex-direction: column;
            align-items: flex-start;
        }

        .mm-instance-card__controls {
            width: 100%;
        }
    }
</style>
