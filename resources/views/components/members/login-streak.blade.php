{{-- resources/views/components/members/login-streak.blade.php --}}
@props(['streak'])

@php
    $done = $streak['done'];
    $remaining = $streak['remaining'];
    $unlocked = $streak['gift_unlocked'];

    // Etapa "atual": o último dia cumprido; se ainda não começou, o 1º dia a conquistar.
    $currentIndex = $done > 0 ? $done : 1;

    $footer = $unlocked
        ? '¡Regalo desbloqueado! 🎉'
        : ($streak['current'] === 0
            ? 'Entra cada día y completa tu racha de 7 días.'
            : '¡'.$remaining.' '.($remaining === 1 ? 'día' : 'días').' más para desbloquear tu regalo!');
@endphp

<section aria-label="Racha de acceso"
         class="ls-card overflow-hidden rounded-3xl border border-brown/15 bg-gradient-to-br from-cream via-cream to-gold/10 px-4 py-5 shadow-sm shadow-brown/5 sm:px-6 sm:py-6">
    <div class="flex items-center justify-between gap-3">
        <p class="flex items-center gap-1.5 font-ui text-xs font-semibold uppercase tracking-wider text-gold">
            <span aria-hidden="true">🔥</span> Racha de acceso
        </p>
        <span class="shrink-0 rounded-full bg-brown/10 px-2.5 py-1 font-ui text-xs font-semibold text-brown">
            {{ $done }}/{{ $streak['goal'] }} {{ $done === 1 ? 'día' : 'días' }}
        </span>
    </div>

    {{-- Trilha de 7 días --}}
    <div class="relative mt-5">
        {{-- Linha de fundo + progresso (atrás dos nós). Recuo lateral = metade de um nó (18px). --}}
        <div class="pointer-events-none absolute inset-x-[18px] top-[18px] h-1.5 -translate-y-1/2 rounded-full bg-brown/15" aria-hidden="true">
            <div class="ls-line h-full rounded-full bg-gradient-to-r from-green-600 to-gold"
                 style="width: {{ $streak['line_percent'] }}%; --ls-target: {{ $streak['line_percent'] }}%"></div>
        </div>

        <ol class="relative flex items-start justify-between">
            @foreach($streak['days'] as $day)
                @php $isCurrent = $day['index'] === $currentIndex; @endphp
                <li class="relative flex flex-col items-center gap-1.5">
                    {{-- Anel pulsante na etapa atual (dopamina ao abrir) --}}
                    @if($isCurrent)
                        <span class="ls-ping pointer-events-none absolute top-0 h-9 w-9 rounded-full
                            {{ ($day['done'] && ! $day['is_gift']) ? 'bg-green-500/40' : 'bg-gold/40' }}"
                              aria-hidden="true"></span>
                    @endif

                    @if($day['is_gift'])
                        {{-- Presente (7º dia) --}}
                        <span @class([
                            'relative flex h-10 w-10 items-center justify-center rounded-full text-lg',
                            'bg-gradient-to-br from-gold to-brown text-cream shadow-md shadow-gold/30 ring-2 ring-gold/40' => $unlocked,
                            'border-2 border-dashed border-brown/30 bg-cream text-brown/40' => ! $unlocked,
                            'ls-current' => $isCurrent,
                        ])>
                            <span aria-hidden="true">🎁</span>
                            @unless($unlocked)
                                <span class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full border border-brown/15 bg-white text-[10px] shadow-sm"
                                      aria-hidden="true">🔒</span>
                            @endunless
                        </span>
                    @elseif($day['done'])
                        {{-- Dia cumprido --}}
                        <span @class([
                            'relative flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-green-600 to-green-700 text-sm text-cream shadow-sm ring-2 ring-green-600/20',
                            'ls-current' => $isCurrent,
                        ]) aria-hidden="true">🔥</span>
                    @else
                        {{-- Dia pendente --}}
                        <span @class([
                            'relative flex h-9 w-9 items-center justify-center rounded-full border-2 bg-cream font-ui text-sm font-semibold',
                            'border-gold/60 text-brown' => $isCurrent,
                            'border-brown/20 text-muted/60' => ! $isCurrent,
                            'ls-current' => $isCurrent,
                        ])>
                            {{ $day['index'] }}
                        </span>
                    @endif

                    <span class="font-ui text-[10px] font-medium uppercase tracking-wide {{ $day['done'] || ($day['is_gift'] && $unlocked) ? 'text-brown' : 'text-muted/50' }}">
                        {{ $day['is_gift'] ? 'Regalo' : 'D'.$day['index'] }}
                    </span>
                </li>
            @endforeach
        </ol>
    </div>

    <p class="mt-4 text-center font-ui text-sm {{ $unlocked ? 'font-semibold text-brown' : 'text-muted' }}">
        {{ $footer }}
    </p>
</section>

@once
    <style>
            @media (prefers-reduced-motion: no-preference) {
                /* Linha de progresso: cresce de 0 até o alvo ao abrir. */
                .ls-line {
                    animation: ls-line-grow 900ms cubic-bezier(0.22, 1, 0.36, 1) 150ms both;
                }
                @keyframes ls-line-grow {
                    from { width: 0; }
                    to   { width: var(--ls-target, 0%); }
                }

                /* Nó da etapa atual: "pop" com leve overshoot. */
                .ls-current {
                    animation: ls-pop 620ms cubic-bezier(0.34, 1.56, 0.64, 1) 780ms both;
                }
                @keyframes ls-pop {
                    0%   { transform: scale(0.55); opacity: 0.5; }
                    60%  { transform: scale(1.18); opacity: 1; }
                    100% { transform: scale(1); }
                }

                /* Anel que irradia da etapa atual (2 pulsos). */
                .ls-ping {
                    animation: ls-ping 1500ms ease-out 850ms 2 both;
                }
                @keyframes ls-ping {
                    0%   { transform: scale(0.85); opacity: 0.65; }
                    100% { transform: scale(2.1); opacity: 0; }
                }
            }
        </style>
@endonce
