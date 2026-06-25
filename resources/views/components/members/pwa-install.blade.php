{{-- resources/views/components/members/pwa-install.blade.php --}}
{{--
    Note: visibility is toggled by swapping the `hidden`/`flex` classes themselves
    (see resources/js/pwa-install.js), never by the native `hidden` attribute.
    Tailwind's [hidden] preflight rule uses :where(), which has zero specificity,
    so a static `flex` class would always win and "hiding" would have no effect.
--}}
<button
    type="button"
    data-pwa-install
    class="hidden w-full items-center gap-3 rounded-2xl border border-brown/30 bg-gradient-to-r from-brown to-gold px-4 py-3.5 text-left text-cream shadow-sm shadow-brown/20 transition hover:brightness-105 active:scale-[0.98]"
>
    <span class="text-2xl" aria-hidden="true">📲</span>
    <span class="min-w-0">
        <span class="block text-sm font-semibold">Agregar aplicación</span>
        <span class="block text-xs text-cream/85">Accede desde tu pantalla de inicio</span>
    </span>
</button>

<div
    id="pwa-install-modal"
    data-pwa-install-modal
    role="dialog"
    aria-modal="true"
    aria-labelledby="pwa-install-modal-title"
    class="hidden fixed inset-0 z-[60] items-center justify-center bg-ink/50 px-4 py-6"
>
    <div class="max-h-[85vh] w-full max-w-sm overflow-y-auto rounded-2xl border border-brown/20 bg-cream p-5 shadow-xl">
        <div class="flex items-start justify-between gap-3">
            <h2 id="pwa-install-modal-title" class="text-base font-semibold text-ink">
                Agrega la Biblia a tu pantalla
            </h2>
            <button
                type="button"
                data-pwa-install-close
                aria-label="Cerrar"
                class="shrink-0 rounded-lg p-1 text-muted/60 transition hover:bg-brown/10 hover:text-muted"
            >
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <p class="mt-2 text-sm text-muted/80">
            Accede a tus lecturas, favoritos y progreso con un solo toque.
        </p>

        <ol data-pwa-modal-ios class="mt-4 list-decimal space-y-1.5 pl-5 text-sm text-muted/90">
            <li>Toca el botón <strong>Compartir</strong> del navegador.</li>
            <li>Selecciona <strong>Agregar a inicio</strong>.</li>
            <li>Activa <strong>Abrir como app</strong>, si aparece.</li>
            <li>Toca <strong>Agregar</strong>.</li>
        </ol>

        <ol data-pwa-modal-generic hidden class="mt-4 list-decimal space-y-1.5 pl-5 text-sm text-muted/90">
            <li>Abre el menú de tu navegador.</li>
            <li>Selecciona <strong>Instalar aplicación</strong> o <strong>Agregar a la pantalla de inicio</strong>.</li>
        </ol>
    </div>
</div>
