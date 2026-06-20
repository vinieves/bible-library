@php
    use App\Support\IntegrationSettings;

    $url = IntegrationSettings::evolutionWebhookUrl();
    $evolutionOk = IntegrationSettings::evolutionConfigured();
@endphp

<div
    x-data="{ open: false }"
    class="flow-webhook-help"
>
    <button
        type="button"
        class="flow-webhook-help__toggle"
        x-on:click="open = ! open"
        x-bind:aria-expanded="open"
    >
        <x-filament::icon
            icon="heroicon-o-information-circle"
            class="h-4 w-4"
        />
        <span>Webhook Evolution — como funciona?</span>
        <x-filament::icon
            icon="heroicon-o-chevron-down"
            class="h-4 w-4 flow-webhook-help__chevron"
            x-bind:class="{ 'flow-webhook-help__chevron--open': open }"
        />
    </button>

    <div
        x-show="open"
        x-collapse
        class="flow-webhook-help__panel"
        style="display: none;"
    >
        <div class="flow-webhook-help__content">
            <p>
                @if ($evolutionOk)
                    <span class="text-success-600 dark:text-success-400">Evolution configurada.</span>
                @else
                    <span class="text-danger-600 dark:text-danger-400">Configure a Evolution em Integrações API.</span>
                @endif
            </p>
            <p>
                Quando um <strong>contato novo</strong> enviar a <strong>primeira mensagem</strong> no WhatsApp,
                este fluxo será disparado <strong>uma única vez</strong> por número.
            </p>
            <p>
                <strong>URL do webhook:</strong><br>
                <code class="text-xs break-all">{{ $url }}</code>
            </p>
            <p><strong>Evento:</strong> <code>MESSAGES_UPSERT</code></p>
            <p>
                <strong>Autenticação:</strong> a Evolution envia o campo <code>apikey</code> no payload
                (mesma chave do painel).
            </p>
            <p>
                Após salvar, use o botão <strong>Registrar webhook na Evolution</strong> no topo desta página.
            </p>
            <p class="text-warning-600 dark:text-warning-400">
                Apenas <strong>um</strong> fluxo de primeira mensagem pode ficar ativo por vez.
            </p>
        </div>
    </div>
</div>

<style>
    .flow-webhook-help {
        margin-top: 0.15rem;
    }

    .flow-webhook-help__toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.65rem;
        border: 1px solid rgb(63 63 70);
        border-radius: 0.5rem;
        background: rgb(39 39 42);
        color: rgb(212 212 216);
        font-size: 0.78rem;
        font-weight: 500;
        line-height: 1.25;
        cursor: pointer;
        transition: border-color 0.15s ease, background 0.15s ease, color 0.15s ease;
    }

    .flow-webhook-help__toggle:hover {
        border-color: rgb(245 158 11 / 0.45);
        background: rgb(48 48 54);
        color: rgb(251 191 36);
    }

    .flow-webhook-help__chevron {
        transition: transform 0.2s ease;
    }

    .flow-webhook-help__chevron--open {
        transform: rotate(180deg);
    }

    .flow-webhook-help__panel {
        overflow: hidden;
    }

    .flow-webhook-help__content {
        margin-top: 0.55rem;
        padding: 0.75rem 0.85rem;
        border: 1px solid rgb(63 63 70);
        border-radius: 0.5rem;
        background: rgb(24 24 27);
        font-size: 0.8rem;
        line-height: 1.5;
        color: rgb(161 161 170);
    }

    .flow-webhook-help__content p {
        margin: 0 0 0.5rem;
    }

    .flow-webhook-help__content p:last-child {
        margin-bottom: 0;
    }
</style>
