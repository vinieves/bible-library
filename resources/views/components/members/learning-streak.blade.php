{{-- resources/views/components/members/learning-streak.blade.php --}}
@props(['streak'])

<section class="mb-4 rounded-2xl border border-member-gold/15 bg-member-card px-4 py-4 sm:px-6 sm:py-5">
    <div class="flex items-center justify-between gap-3">
        <p class="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-member-body/65">
            <span aria-hidden="true">📈</span> Días de aprendizaje seguidos
        </p>
        <span class="shrink-0 text-sm font-semibold text-member-gold-dark">
            {{ $streak['days'] }} {{ $streak['days'] === 1 ? 'día' : 'días' }}
        </span>
    </div>
    <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-member-gold/15">
        <div class="h-full rounded-full bg-member-gold" style="width: {{ $streak['percent'] }}%"></div>
    </div>
</section>
