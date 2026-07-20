{{-- Lista de dispositivos inscritos em push (usado em ManagePushSettings). --}}
<div class="space-y-3">
    <p class="text-sm text-gray-600 dark:text-gray-300">
        Total de dispositivos inscritos: <strong>{{ $total }}</strong>
        · Usuários distintos: <strong>{{ $distinctUsers }}</strong>
    </p>

    @if ($subscriptions->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Nenhum dispositivo inscrito ainda.
        </p>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    <tr>
                        <th class="px-3 py-2 font-medium">Usuário</th>
                        <th class="px-3 py-2 font-medium">E-mail</th>
                        <th class="px-3 py-2 font-medium">Dispositivo</th>
                        <th class="px-3 py-2 font-medium">Inscrito em</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($subscriptions as $subscription)
                        <tr class="text-gray-700 dark:text-gray-200">
                            <td class="px-3 py-2">{{ $subscription->user?->name ?? 'Anônimo' }}</td>
                            <td class="px-3 py-2">{{ $subscription->user?->email ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-500 dark:text-gray-400">
                                {{ \Illuminate\Support\Str::limit($subscription->user_agent ?? '—', 40) }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ $subscription->created_at?->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($total > $subscriptions->count())
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Mostrando os {{ $subscriptions->count() }} mais recentes de {{ $total }}.
            </p>
        @endif
    @endif
</div>
