{{-- Lista de dispositivos inscritos em push (usado em ManagePushSettings). --}}
<div class="ps-subs">
    <div class="ps-subs__summary">
        <x-filament::badge color="primary" size="lg">
            {{ $total }} {{ \Illuminate\Support\Str::plural('dispositivo', $total) }}
        </x-filament::badge>
        <x-filament::badge color="gray" size="lg">
            {{ $distinctUsers }} {{ \Illuminate\Support\Str::plural('usuário', $distinctUsers) }}
        </x-filament::badge>
    </div>

    @if ($subscriptions->isEmpty())
        <div class="ps-subs__empty">
            <x-filament::icon icon="heroicon-o-bell-slash" class="h-6 w-6" />
            <p>Nenhum dispositivo inscrito ainda.</p>
        </div>
    @else
        <div class="ps-subs__table-wrap fi-section">
            <table class="ps-subs__table">
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>E-mail</th>
                        <th>Dispositivo</th>
                        <th>Inscrito em</th>
                        <th class="ps-subs__col-action">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($subscriptions as $subscription)
                        <tr wire:key="sub-{{ $subscription->id }}">
                            <td>
                                <span class="ps-subs__name">
                                    {{ $subscription->user?->name ?? 'Anônimo' }}
                                </span>
                            </td>
                            <td class="ps-subs__muted">{{ $subscription->user?->email ?? '—' }}</td>
                            <td class="ps-subs__muted">
                                {{ \Illuminate\Support\Str::limit($subscription->user_agent ?? '—', 38) }}
                            </td>
                            <td class="ps-subs__muted ps-subs__nowrap">
                                {{ $subscription->created_at?->format('d/m/Y H:i') }}
                            </td>
                            <td class="ps-subs__col-action">
                                <x-filament::button
                                    size="xs"
                                    color="gray"
                                    icon="heroicon-o-paper-airplane"
                                    wire:click="sendTestToSubscription({{ $subscription->id }})"
                                    wire:target="sendTestToSubscription({{ $subscription->id }})"
                                    wire:loading.attr="disabled"
                                >
                                    Testar
                                </x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($total > $subscriptions->count())
            <p class="ps-subs__more">
                Mostrando os {{ $subscriptions->count() }} mais recentes de {{ $total }}.
            </p>
        @endif
    @endif
</div>

<style>
    .ps-subs {
        display: grid;
        gap: 0.85rem;
    }

    .ps-subs__summary {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .ps-subs__table-wrap {
        margin: 0;
        overflow-x: auto;
        border-radius: 0.75rem;
        border: 1px solid rgb(228 228 231);
    }

    .dark .ps-subs__table-wrap {
        border-color: rgb(63 63 70);
    }

    .ps-subs__table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.8125rem;
    }

    .ps-subs__table thead th {
        text-align: left;
        font-weight: 600;
        padding: 0.6rem 0.85rem;
        background: rgb(249 250 251);
        color: rgb(82 82 91);
        white-space: nowrap;
    }

    .dark .ps-subs__table thead th {
        background: rgb(39 39 42);
        color: rgb(212 212 216);
    }

    .ps-subs__table tbody tr {
        border-top: 1px solid rgb(244 244 245);
    }

    .dark .ps-subs__table tbody tr {
        border-top-color: rgb(39 39 42);
    }

    .ps-subs__table tbody tr:hover {
        background: rgb(250 250 250);
    }

    .dark .ps-subs__table tbody tr:hover {
        background: rgba(63, 63, 70, 0.35);
    }

    .ps-subs__table td {
        padding: 0.55rem 0.85rem;
        vertical-align: middle;
        color: rgb(63 63 70);
    }

    .dark .ps-subs__table td {
        color: rgb(228 228 231);
    }

    .ps-subs__name {
        font-weight: 600;
    }

    .ps-subs__muted {
        color: rgb(113 113 122);
    }

    .dark .ps-subs__muted {
        color: rgb(161 161 170);
    }

    .ps-subs__nowrap {
        white-space: nowrap;
    }

    .ps-subs__col-action {
        text-align: right;
        white-space: nowrap;
    }

    .ps-subs__more {
        margin: 0;
        font-size: 0.75rem;
        color: rgb(113 113 122);
    }

    .ps-subs__empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        padding: 1.5rem;
        text-align: center;
        color: rgb(113 113 122);
    }

    .dark .ps-subs__empty {
        color: rgb(161 161 170);
    }
</style>
