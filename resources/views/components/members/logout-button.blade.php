<form method="POST" action="{{ route('logout') }}" class="shrink-0">
    @csrf
    <button type="submit"
            class="member-header-logout"
            aria-label="Salir de la cuenta">
        <svg class="h-[1.125rem] w-[1.125rem] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        <span>Salir</span>
    </button>
</form>
