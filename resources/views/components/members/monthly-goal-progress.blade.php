{{-- resources/views/components/members/monthly-goal-progress.blade.php --}}
@props(['goal'])

<section class="dashboard-card flex items-center justify-between gap-4 rounded-2xl border border-member-gold/15 bg-member-card px-4 py-4 sm:px-6 sm:py-5">
    <div class="flex items-center gap-4">
        <div class="relative flex h-20 w-20 shrink-0 items-center justify-center rounded-full"
             style="background: conic-gradient(var(--member-gold, #b8860b) {{ $goal['percent'] }}%, rgba(184,134,11,0.15) 0);">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-member-card text-sm font-semibold text-member-title">
                {{ $goal['percent'] }}%
            </div>
        </div>
        <div>
            <p class="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-member-body/65">
                <span aria-hidden="true">📈</span> Tu progreso
            </p>
            <p class="mt-1 text-sm text-member-body">
                {{ $goal['label'] }}
            </p>
        </div>
    </div>
</section>
