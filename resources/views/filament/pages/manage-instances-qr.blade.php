<div class="mm-instances-qr">
    <div class="mm-instances-qr__header">
        <div>
            <p class="mm-instances-qr__title">
                Instância: <strong>{{ $qrInstance }}</strong>
            </p>
            <p class="mm-instances-qr__hint">
                Abra o WhatsApp no celular → Menu → Aparelhos conectados → Conectar aparelho.
            </p>
        </div>

        <div class="mm-instances-qr__actions">
            <x-filament::button
                color="gray"
                size="sm"
                icon="heroicon-o-arrow-path"
                wire:click="connectInstance('{{ $qrInstance }}')"
            >
                Atualizar QR
            </x-filament::button>

            <x-filament::button
                color="gray"
                size="sm"
                wire:click="closeQrModal"
            >
                Fechar
            </x-filament::button>
        </div>
    </div>

    <div class="mm-instances-qr__body">
        <div class="mm-instances-qr__image-wrap">
            <img
                src="{{ $qrBase64 }}"
                alt="QR Code WhatsApp — {{ $qrInstance }}"
                class="mm-instances-qr__image"
            />
        </div>

        <div class="mm-instances-qr__details">
            @if (filled($qrPairingCode))
                <div class="mm-instances-qr__detail">
                    <span class="mm-instances-qr__detail-label">Código de pareamento</span>
                    <code class="mm-instances-qr__detail-value">{{ $qrPairingCode }}</code>
                </div>
            @endif

            @if (filled($qrCode))
                <div class="mm-instances-qr__detail">
                    <span class="mm-instances-qr__detail-label">Código</span>
                    <code class="mm-instances-qr__detail-value mm-instances-qr__detail-value--truncate">
                        {{ \Illuminate\Support\Str::limit($qrCode, 48) }}
                    </code>
                </div>
            @endif

            <p class="mm-instances-qr__note">
                Após escanear, clique em <strong>Atualizar</strong> na página para ver o status conectado.
                Depois defina a instância como ativa para envios.
            </p>
        </div>
    </div>
</div>

<style>
    .mm-instances-qr__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .mm-instances-qr__title {
        margin: 0 0 0.25rem;
        font-size: 0.9rem;
        color: rgb(250 250 250);
    }

    .mm-instances-qr__hint {
        margin: 0;
        font-size: 0.8rem;
        line-height: 1.45;
        color: rgb(161 161 170);
    }

    .mm-instances-qr__actions {
        display: flex;
        flex-shrink: 0;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .mm-instances-qr__body {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        gap: 1.5rem;
    }

    .mm-instances-qr__image-wrap {
        padding: 0.75rem;
        border-radius: 0.75rem;
        background: rgb(255 255 255);
        border: 1px solid rgb(63 63 70);
    }

    .mm-instances-qr__image {
        display: block;
        width: 14rem;
        height: 14rem;
        object-fit: contain;
    }

    .mm-instances-qr__details {
        flex: 1;
        min-width: 12rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .mm-instances-qr__detail-label {
        display: block;
        margin-bottom: 0.2rem;
        font-size: 0.75rem;
        color: rgb(161 161 170);
    }

    .mm-instances-qr__detail-value {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        background: rgb(39 39 42);
        font-size: 0.85rem;
        color: rgb(250 250 250);
    }

    .mm-instances-qr__detail-value--truncate {
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .mm-instances-qr__note {
        margin: 0;
        font-size: 0.8rem;
        line-height: 1.5;
        color: rgb(161 161 170);
    }

    @media (max-width: 640px) {
        .mm-instances-qr__header {
            flex-direction: column;
        }

        .mm-instances-qr__actions {
            width: 100%;
        }
    }
</style>
