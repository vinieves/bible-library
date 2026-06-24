{{-- resources/views/components/members/verse-of-the-day.blade.php --}}
@props(['verse'])

@if($verse)
    <section class="dashboard-card rounded-2xl border border-member-gold/15 bg-member-card px-4 py-4 sm:px-6 sm:py-5">
        <p class="mb-2 flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-member-body/65">
            <span aria-hidden="true">📖</span> Versículo del día
        </p>
        <p class="text-base italic leading-relaxed text-member-title sm:text-lg">
            “{{ $verse['text'] }}”
        </p>
        <p class="mt-2 text-sm font-semibold text-member-gold-dark">
            {{ $verse['reference'] }}
        </p>
    </section>
@endif
