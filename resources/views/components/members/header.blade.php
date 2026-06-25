@props([
    'showBack' => false,
    'pageTitle' => null,
    'backUrl' => null,
])

@php
    $backHref = $backUrl ?? (url()->previous() !== url()->current() ? url()->previous() : route('members.dashboard'));
@endphp

<header class="sticky top-0 z-40 border-b border-gold/10 bg-ink backdrop-blur-md">
    <div class="mx-auto max-w-3xl px-4 sm:px-6">
        <div class="flex min-h-[3.75rem] items-center justify-between gap-3 py-3 sm:min-h-[4rem]">
            <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                @if($showBack)
                    <a href="{{ $backHref }}"
                       class="inline-flex shrink-0 items-center gap-1 rounded-xl px-2.5 py-2.5 font-ui text-base font-medium text-cream transition hover:bg-cream/10 active:scale-[0.98] sm:gap-1.5 sm:px-3">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="hidden min-[380px]:inline">Volver</span>
                    </a>
                    @if($pageTitle)
                        <h1 class="truncate font-display text-base font-semibold leading-snug text-cream sm:text-lg md:text-xl">
                            {{ $pageTitle }}
                        </h1>
                    @endif
                @else
                    <div class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
                        <div class="member-header-brand-icon overflow-hidden" aria-hidden="true">
                            <img src="{{ asset('images/logo.png') }}" alt="" class="h-full w-full object-cover">
                        </div>
                        <div class="min-w-0">
                            <p class="font-ui text-xs text-gold sm:text-sm">Bienvenido</p>
                            <p class="truncate font-display text-base font-semibold text-cream sm:text-xl">
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
