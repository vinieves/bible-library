{{-- resources/views/components/members/learning-streak.blade.php --}}
@props(['streak'])

<section class="mb-4 rounded-2xl border border-brown/15 bg-cream px-4 py-4 sm:px-6 sm:py-5">
    <div class="flex items-center justify-between gap-3">
        <p class="flex items-center gap-2 font-ui text-xs font-medium uppercase tracking-wider text-gold">
            <span aria-hidden="true">🙏</span> Días seguidos
        </p>
        <span class="shrink-0 font-ui text-sm font-semibold text-brown">
            {{ $streak['days'] }} {{ $streak['days'] === 1 ? 'día' : 'días' }}
        </span>
    </div>
    <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-brown/15">
        <div class="h-full rounded-full bg-green-600" style="width: {{ $streak['percent'] }}%"></div>
    </div>
</section>
