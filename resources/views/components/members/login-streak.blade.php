{{-- resources/views/components/members/login-streak.blade.php --}}
@props(['streak'])

@php
    $done = $streak['done'];
    $remaining = $streak['remaining'];
    $unlocked = $streak['gift_unlocked'];

    $footer = $unlocked
        ? '¡Regalo desbloqueado! 🎉'
        : ($streak['current'] === 0
            ? 'Entra cada día y completa tu racha de 7 días.'
            : '¡'.$remaining.' '.($remaining === 1 ? 'día' : 'días').' más para desbloquear tu regalo!');
@endphp

<section aria-label="Racha de acceso"
         class="overflow-hidden rounded-3xl border border-brown/15 bg-gradient-to-br from-cream via-cream to-gold/10 px-4 py-5 shadow-sm shadow-brown/5 sm:px-6 sm:py-6">
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
            <div class="h-full rounded-full bg-gradient-to-r from-green-600 to-gold transition-[width] duration-500"
                 style="width: {{ $streak['line_percent'] }}%"></div>
        </div>

        <ol class="relative flex items-start justify-between">
            @foreach($streak['days'] as $day)
                <li class="flex flex-col items-center gap-1.5">
                    @if($day['is_gift'])
                        {{-- Presente (7º dia) --}}
                        <span class="relative flex h-10 w-10 items-center justify-center rounded-full text-lg
                            {{ $unlocked
                                ? 'bg-gradient-to-br from-gold to-brown text-cream shadow-md shadow-gold/30 ring-2 ring-gold/40 motion-safe:animate-pulse'
                                : 'border-2 border-dashed border-brown/30 bg-cream text-brown/40' }}">
                            <span aria-hidden="true">🎁</span>
                            @unless($unlocked)
                                <span class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full border border-brown/15 bg-white text-[10px] shadow-sm"
                                      aria-hidden="true">🔒</span>
                            @endunless
                        </span>
                    @elseif($day['done'])
                        {{-- Dia cumprido --}}
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-green-600 to-green-700 text-sm text-cream shadow-sm ring-2 ring-green-600/20"
                              aria-hidden="true">🔥</span>
                    @else
                        {{-- Dia pendente --}}
                        <span class="flex h-9 w-9 items-center justify-center rounded-full border-2 border-brown/20 bg-cream font-ui text-sm font-semibold text-muted/60">
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
