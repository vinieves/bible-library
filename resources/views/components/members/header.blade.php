@props([
    'showBack' => false,
    'pageTitle' => null,
    'backUrl' => null,
])

@php
    $backHref = $backUrl ?? (url()->previous() !== url()->current() ? url()->previous() : route('members.dashboard'));
@endphp

<header class="sticky top-0 z-40 border-b border-member-gold/20 bg-member-card/95 backdrop-blur-md supports-[backdrop-filter]:bg-member-card/90">
    <div class="mx-auto max-w-3xl px-4 sm:px-6">
        <div class="flex min-h-[3.75rem] items-center justify-between gap-3 py-3 sm:min-h-[4rem]">
            <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                @if($showBack)
                    <a href="{{ $backHref }}"
                       class="inline-flex shrink-0 items-center gap-1 rounded-xl px-2.5 py-2.5 text-base font-medium text-member-gold transition hover:bg-member-gold/10 active:scale-[0.98] sm:gap-1.5 sm:px-3">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="hidden min-[380px]:inline">Volver</span>
                    </a>
                    @if($pageTitle)
                        <h1 class="truncate text-base font-semibold leading-snug text-member-title sm:text-lg md:text-xl">
                            {{ $pageTitle }}
                        </h1>
                    @endif
                @else
                    <div class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
                        <div class="member-header-brand-icon overflow-hidden" aria-hidden="true">
                            <img src="{{ asset('images/logo.png') }}" alt="" class="h-full w-full object-cover">
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-member-body/70 sm:text-sm">Bienvenido</p>
                            <p class="truncate text-base font-semibold text-member-gold sm:text-xl">
                                {{ auth()->user()->name }}
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            @unless($showBack)
                <x-members.logout-button />
            @endunless
        </div>
    </div>
</header>
