@extends('layouts.members', ['showBack' => true])

@section('title', 'La Biblia Explicada')

@section('content')
    @if(! $bibleAvailable)
        <div class="rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-6 text-center">
            <p class="text-base text-bible-cream/80">La Biblia no está disponible en este momento.</p>
            <p class="mt-2 text-sm text-bible-cream/50">Contacte al soporte si el problema persiste.</p>
        </div>
    @else
        <div
            x-data="bibleReader({
                booksUrl: @js($booksUrl),
                chapterUrl: @js($chapterUrl),
            })"
            class="bible-reader space-y-4"
            @click.outside="bookOpen = false"
        >
            <p class="text-sm text-bible-cream/60">Elija un libro y un capítulo para leer la explicación.</p>

            <template x-if="loadError">
                <div class="rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-4 text-sm text-bible-cream/80" x-text="loadError"></div>
            </template>

            {{-- Paso 1: libro y capítulo --}}
            <section class="bible-reader-card">
                <div class="bible-reader-card-head">
                    <span class="bible-reader-step">1</span>
                    <div>
                        <h2 class="bible-reader-card-title">Elige tu lectura</h2>
                        <p class="bible-reader-card-subtitle">Escribe o selecciona un libro y un capítulo.</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="relative">
                        <label class="bible-reader-label">Libro</label>
                        <div class="bible-reader-combobox">
                            <input
                                type="text"
                                x-model="bookQuery"
                                @focus="openBookPicker()"
                                @input="onBookInput()"
                                placeholder="Ej.: Génesis"
                                autocomplete="off"
                                class="bible-reader-input"
                                :disabled="loadingBooks"
                            >
                            <button
                                type="button"
                                class="bible-reader-combobox-toggle"
                                @click="bookOpen = !bookOpen; if (bookOpen) bookQuery = ''"
                                :disabled="loadingBooks"
                                aria-label="Mostrar libros"
                            >
                                <svg class="h-4 w-4 transition" :class="bookOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </div>

                        <div x-show="bookOpen" x-cloak class="bible-reader-dropdown">
                            <template x-if="loadingBooks">
                                <p class="bible-reader-dropdown-empty">Cargando la Biblia…</p>
                            </template>
                            <template x-if="!loadingBooks && filteredBooks.length === 0">
                                <p class="bible-reader-dropdown-empty">No se encontraron libros.</p>
                            </template>
                            <template x-for="book in filteredBooks" :key="book.abbr">
                                <button
                                    type="button"
                                    class="bible-reader-dropdown-item"
                                    @click="selectBook(book)"
                                >
                                    <span class="font-medium text-bible-cream" x-text="book.name"></span>
                                    <span class="text-xs text-bible-cream/45" x-text="book.abbr"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="bible-reader-label">Capítulo</label>
                        <select
                            class="bible-reader-select"
                            :disabled="!selectedBook || loadingBooks"
                            x-model="selectedChapter"
                            @change="selectedChapter && selectChapter(Number(selectedChapter))"
                        >
                            <option value="" x-text="selectedBook ? 'Seleccione…' : 'Primero elija un libro'"></option>
                            <template x-for="chapter in chapters" :key="chapter">
                                <option :value="chapter" x-text="chapter"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <p
                    x-show="selectedBook && selectedChapter && verses.length"
                    x-cloak
                    class="bible-reader-meta"
                    x-text="`Capítulo ${selectedChapter}: ${verseCountLabel}.`"
                ></p>
            </section>

            {{-- Paso 3: lectura del versículo --}}
            <section x-show="selectedVerse" x-cloak x-ref="verseDetail" class="bible-reader-card bible-reader-detail">
                <div class="bible-reader-card-head bible-reader-card-head-split">
                    <div class="flex items-start gap-3">
                        <span class="bible-reader-step">3</span>
                        <p class="bible-reader-card-subtitle mt-1">Lectura y explicación</p>
                    </div>
                    <button type="button" class="bible-reader-link-btn" @click="closeVerse()">
                        Ver otros
                    </button>
                </div>

                <p class="bible-reader-reference" x-text="verseReference"></p>
                <blockquote class="bible-reader-verse-text" x-text="selectedVerse?.text"></blockquote>

                <div class="bible-reader-explanation">
                    <p class="bible-reader-explanation-label">Explicación</p>
                    <p class="bible-reader-explanation-text" x-text="selectedVerse?.explanation"></p>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" class="bible-reader-action bible-reader-action-secondary" @click="copyVerse()">
                        Copiar
                    </button>
                    <button type="button" class="bible-reader-action bible-reader-action-primary" @click="shareVerse()">
                        Compartir
                    </button>
                </div>
            </section>

            {{-- Paso 2: lista de versículos --}}
            <section class="bible-reader-card">
                <div class="bible-reader-card-head">
                    <span class="bible-reader-step">2</span>
                    <div>
                        <h2 class="bible-reader-card-title">Versículos del capítulo</h2>
                        <p
                            class="bible-reader-card-subtitle"
                            x-text="selectedBook && selectedChapter
                                ? `${selectedBook.name} · Capítulo ${selectedChapter}`
                                : 'Primero seleccione un libro y un capítulo.'"
                        ></p>
                    </div>
                </div>

                <template x-if="selectedBook && selectedChapter">
                    <div class="mt-4">
                        <div class="bible-reader-search">
                            <svg class="h-4 w-4 shrink-0 text-bible-cream/35" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input
                                type="search"
                                x-model="verseQuery"
                                placeholder="Busca un número o una palabra"
                                class="bible-reader-search-input"
                            >
                        </div>

                        <div class="bible-reader-list-meta">
                            <span x-text="`${filteredVerses.length} versículo${filteredVerses.length === 1 ? '' : 's'}`"></span>
                            <span>Toca un versículo para abrirlo</span>
                        </div>

                        <template x-if="loadingChapter">
                            <p class="py-8 text-center text-sm text-bible-cream/50">Cargando versículos…</p>
                        </template>

                        <template x-if="chapterError">
                            <p class="py-6 text-center text-sm text-red-300/80" x-text="chapterError"></p>
                        </template>

                        <template x-if="!loadingChapter && !chapterError && filteredVerses.length === 0">
                            <p class="py-8 text-center text-sm text-bible-cream/50">No se encontraron versículos.</p>
                        </template>

                        <div x-show="!loadingChapter && filteredVerses.length" class="mt-3 space-y-2">
                            <template x-for="verse in filteredVerses" :key="verse.number">
                                <button
                                    type="button"
                                    class="bible-reader-verse-card"
                                    :class="selectedVerse?.number === verse.number ? 'bible-reader-verse-card-active' : ''"
                                    @click="openVerse(verse)"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="bible-reader-verse-label" x-text="`Versículo ${verse.number}`"></span>
                                        <svg class="h-4 w-4 shrink-0 text-bible-cream/30" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                    <p class="bible-reader-verse-preview" x-text="truncate(verse.text)"></p>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="!selectedBook || !selectedChapter">
                    <div class="bible-reader-empty">
                        <div class="bible-reader-empty-icon">☼</div>
                        <h3 class="text-base font-semibold text-bible-cream/80">Elige un capítulo para comenzar</h3>
                        <p class="mt-1 max-w-sm text-sm text-bible-cream/45">
                            Primero selecciona un libro. Después elige un capítulo para ver todos sus versículos.
                        </p>
                    </div>
                </template>
            </section>
        </div>
    @endif
@endsection
