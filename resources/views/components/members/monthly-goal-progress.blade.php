{{-- resources/views/components/members/monthly-goal-progress.blade.php --}}
@props(['goal'])

<section class="flex items-center justify-between gap-4 rounded-2xl border border-brown/15 bg-cream px-4 py-4 sm:px-6 sm:py-5">
    <div class="flex items-center gap-4">
        <div class="relative flex h-20 w-20 shrink-0 items-center justify-center rounded-full"
             style="background: conic-gradient(var(--brown) {{ $goal['percent'] }}%, rgb(var(--brown-channel) / 0.15) 0);">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-cream text-sm font-semibold text-ink">
                {{ $goal['percent'] }}%
            </div>
        </div>
        <div>
            <p class="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-muted/65">
                <span aria-hidden="true">📈</span> Tu progreso
            </p>
            <p class="mt-1 text-sm text-muted">
                {{ $goal['label'] }}
            </p>
        </div>
    </div>
</section>
