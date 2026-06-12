@props([
    'showBack' => false,
    'pageTitle' => null,
    'backUrl' => null,
])

@php
    $backHref = $backUrl ?? (url()->previous() !== url()->current() ? url()->previous() : route('members.dashboard'));
@endphp

<header class="sticky top-0 z-40 border-b border-bible-gold/15 bg-bible-dark/95 backdrop-blur supports-[backdrop-filter]:bg-bible-dark/80">
    <div class="mx-auto max-w-3xl px-4 sm:px-6">
        <div class="flex min-h-[3.75rem] items-center justify-between gap-3 py-3 sm:min-h-[4rem]">
            <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                @if($showBack)
                    <a href="{{ $backHref }}"
                       class="inline-flex shrink-0 items-center gap-1 rounded-xl px-2.5 py-2.5 text-base font-medium text-bible-gold transition hover:bg-bible-gold/10 active:scale-[0.98] sm:gap-1.5 sm:px-3">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="hidden min-[380px]:inline">Volver</span>
                    </a>
                    @if($pageTitle)
                        <h1 class="truncate text-base font-semibold leading-snug text-bible-cream sm:text-lg md:text-xl">
                            {{ $pageTitle }}
                        </h1>
                    @endif
                @else
                    <div class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
                        <div class="member-header-brand-icon" aria-hidden="true">
                            <svg class="h-5 w-5 sm:h-[1.375rem] sm:w-[1.375rem]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-bible-cream/55 sm:text-sm">Bienvenido</p>
                            <p class="truncate text-base font-semibold text-bible-gold sm:text-xl">
                                {{ auth()->user()->name }}
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <x-members.logout-button />
        </div>
    </div>
</header>
