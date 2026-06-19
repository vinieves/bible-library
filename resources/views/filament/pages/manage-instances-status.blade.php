<div class="mm-instances-status">
    <div class="mm-instances-status__grid">
        <div class="mm-instances-status__item">
            <span class="mm-instances-status__label">URL Evolution API</span>
            @if ($apiReady && filled($baseUrl))
                <span class="mm-instances-status__value text-success-600 dark:text-success-400">
                    {{ $baseUrl }}
                </span>
            @else
                <span class="mm-instances-status__value text-danger-600 dark:text-danger-400">
                    Não configurada — vá em <strong>Integrações API</strong>.
                </span>
            @endif
        </div>

        <div class="mm-instances-status__item">
            <span class="mm-instances-status__label">API Key</span>
            @if ($apiReady)
                <span class="mm-instances-status__value text-success-600 dark:text-success-400">
                    Configurada
                </span>
            @else
                <span class="mm-instances-status__value text-danger-600 dark:text-danger-400">
                    Não configurada — vá em <strong>Integrações API</strong>.
                </span>
            @endif
        </div>

        <div class="mm-instances-status__item">
            <span class="mm-instances-status__label">Instância ativa (envios)</span>
            @if (filled($activeInstance))
                <span class="mm-instances-status__value text-success-600 dark:text-success-400">
                    {{ $activeInstance }}
                </span>
            @else
                <span class="mm-instances-status__value text-warning-600 dark:text-warning-400">
                    Nenhuma — defina uma instância conectada abaixo.
                </span>
            @endif
        </div>

        <div class="mm-instances-status__item">
            <span class="mm-instances-status__label">Pronto para envios</span>
            @if ($configured)
                <span class="mm-instances-status__value text-success-600 dark:text-success-400">
                    Sim — mensagens e fluxos usarão {{ $activeInstance }}.
                </span>
            @else
                <span class="mm-instances-status__value text-warning-600 dark:text-warning-400">
                    Configure URL, API Key e defina uma instância ativa.
                </span>
            @endif
        </div>
    </div>
</div>

<style>
    .mm-instances-status__grid {
        display: grid;
        gap: 0.75rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .mm-instances-status__item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .mm-instances-status__label {
        font-size: 0.75rem;
        font-weight: 500;
        color: rgb(161 161 170);
    }

    .mm-instances-status__value {
        font-size: 0.875rem;
        line-height: 1.45;
    }

    @media (max-width: 640px) {
        .mm-instances-status__grid {
            grid-template-columns: 1fr;
        }
    }
</style>
