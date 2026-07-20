{{-- resources/views/components/members/app-onboarding.blade.php --}}
{{--
    Pop-up de onboarding: mostrado a cada abertura do app enquanto os 2 passos
    (agregar a pantalla de inicio + activar notificaciones) não estiverem feitos.
    Lógica em resources/js/app-onboarding.js (módulo autocontido, não depende de
    pwa-install.js).

    Nota: a visibilidade é alternada trocando as classes `hidden`/`flex` (não o
    atributo `hidden` nativo). A regra [hidden] do preflight do Tailwind usa
    :where() (especificidade zero), então uma classe `flex` estática sempre
    venceria e "esconder" não teria efeito.
--}}
<div
    id="app-onboarding-modal"
    data-onboarding-modal
    role="dialog"
    aria-modal="true"
    aria-labelledby="app-onboarding-title"
    class="hidden fixed inset-0 z-[70] items-center justify-center bg-ink/50 px-4 py-6"
>
    <div class="relative max-h-[85vh] w-full max-w-sm overflow-y-auto rounded-2xl border border-brown/20 bg-cream p-5 shadow-xl">
        {{-- X pequeno: fecha só a sessão (reaparece na próxima abertura). --}}
        <button
            type="button"
            data-onboarding-close
            aria-label="Cerrar"
            class="absolute right-2 top-2 rounded p-0.5 text-xs leading-none text-muted/50 transition hover:text-muted"
        >
            <span aria-hidden="true">&times;</span>
        </button>

        <h2 id="app-onboarding-title" class="pr-5 text-base font-semibold text-ink">
            Aprovecha la Biblia al máximo
        </h2>
        <p class="mt-1.5 text-sm text-muted/80">
            Completa estos dos pasos para acceder más rápido y no perderte nada.
        </p>

        <div class="mt-4 space-y-3">
            {{-- Passo 1: agregar a pantalla de inicio --}}
            <button
                type="button"
                data-onboarding-a2hs
                class="flex w-full items-center gap-3 rounded-2xl border border-brown/30 bg-gradient-to-r from-brown to-gold px-4 py-3.5 text-left text-cream shadow-sm shadow-brown/20 transition hover:brightness-105 active:scale-[0.98]"
            >
                <span class="text-2xl" aria-hidden="true">📲</span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold">Agregar a pantalla de inicio</span>
                    <span class="block text-xs text-cream/85">Abre la app con un solo toque</span>
                </span>
                <span data-onboarding-a2hs-check hidden class="text-lg" aria-hidden="true">✅</span>
            </button>

            {{-- Instruções iOS inline (só aparecem em iOS ao tocar o passo 1). --}}
            <ol data-onboarding-ios hidden class="list-decimal space-y-1.5 rounded-xl bg-brown/5 px-5 py-3 text-sm text-muted/90">
                <li>Toca el botón <strong>Compartir</strong> del navegador.</li>
                <li>Selecciona <strong>Agregar a inicio</strong>.</li>
                <li>Activa <strong>Abrir como app</strong>, si aparece.</li>
                <li>Toca <strong>Agregar</strong>.</li>
            </ol>

            {{-- Instruções genéricas inline (desktop sem prompt nativo). --}}
            <ol data-onboarding-generic hidden class="list-decimal space-y-1.5 rounded-xl bg-brown/5 px-5 py-3 text-sm text-muted/90">
                <li>Abre el menú de tu navegador.</li>
                <li>Selecciona <strong>Instalar aplicación</strong> o <strong>Agregar a la pantalla de inicio</strong>.</li>
            </ol>

            {{-- Passo 2: activar notificaciones --}}
            <button
                type="button"
                data-onboarding-notify
                class="flex w-full items-center gap-3 rounded-2xl border border-brown/30 bg-white px-4 py-3.5 text-left text-ink shadow-sm shadow-brown/10 transition hover:bg-brown/5 active:scale-[0.98]"
            >
                <span class="text-2xl" aria-hidden="true">🔔</span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold">Activar notificaciones</span>
                    <span data-onboarding-notify-sub class="block text-xs text-muted/70">Recibe avisos importantes</span>
                </span>
                <span data-onboarding-notify-check hidden class="text-lg" aria-hidden="true">✅</span>
            </button>
        </div>
    </div>
</div>
