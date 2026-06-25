{{-- resources/views/components/members/verse-of-the-day.blade.php --}}
@props(['verse'])

@if($verse)
    <section class="rounded-2xl border border-brown/15 bg-cream px-4 py-4 sm:px-6 sm:py-5">
        <p class="mb-2 flex items-center gap-2 font-ui text-xs font-medium uppercase tracking-wider text-gold">
            <span aria-hidden="true">📖</span> Versículo del día
        </p>
        <p class="font-display text-base italic leading-relaxed text-ink sm:text-lg">
            “{{ $verse['text'] }}”
        </p>
        <p class="mt-2 font-ui text-sm font-semibold text-brown">
            {{ $verse['reference'] }}
        </p>
    </section>
@endif
