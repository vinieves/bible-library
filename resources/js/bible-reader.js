document.addEventListener('alpine:init', () => {
    Alpine.data('bibleReader', (config) => ({
        books: [],
        loadingBooks: true,
        loadError: null,
        bookQuery: '',
        bookOpen: false,
        selectedBook: null,
        selectedChapter: '',
        chapters: [],
        verses: [],
        loadingChapter: false,
        chapterError: null,
        verseQuery: '',
        selectedVerse: null,

        async init() {
            try {
                const response = await fetch(config.booksUrl, {
                    headers: { Accept: 'application/json' },
                });

                if (! response.ok) {
                    throw new Error('books');
                }

                this.books = await response.json();
                await this.restoreProgress();
            } catch {
                this.loadError = 'No se pudo cargar la Biblia. Intente de nuevo más tarde.';
            } finally {
                this.loadingBooks = false;
            }
        },

        async restoreProgress() {
            if (! config.initialBook) {
                return;
            }

            const book = this.books.find((item) => item.abbr === config.initialBook);

            if (! book) {
                return;
            }

            this.selectBook(book);

            if (! config.initialChapter) {
                return;
            }

            await this.selectChapter(Number(config.initialChapter), { skipSave: true });

            if (! config.initialVerse) {
                return;
            }

            const verse = this.verses.find((item) => item.number === Number(config.initialVerse));

            if (verse) {
                this.openVerse(verse, { skipSave: true });
            }
        },

        async saveProgress(verse = null) {
            if (! config.progressUrl || ! this.selectedBook || ! this.selectedChapter) {
                return;
            }

            try {
                await fetch(config.progressUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                    },
                    body: JSON.stringify({
                        book_abbr: this.selectedBook.abbr,
                        chapter: Number(this.selectedChapter),
                        verse: verse?.number ?? this.selectedVerse?.number ?? null,
                    }),
                });
            } catch {
                // El progreso se reintentará en la próxima interacción.
            }
        },

        get filteredBooks() {
            const query = this.bookQuery.toLowerCase().trim();

            if (! query) {
                return this.books;
            }

            return this.books.filter((book) =>
                book.name.toLowerCase().includes(query)
                || book.abbr.toLowerCase().includes(query),
            );
        },

        get filteredVerses() {
            const query = this.verseQuery.toLowerCase().trim();

            if (! query) {
                return this.verses;
            }

            return this.verses.filter((verse) =>
                String(verse.number).includes(query)
                || verse.text.toLowerCase().includes(query),
            );
        },

        get verseCountLabel() {
            if (! this.selectedBook || ! this.selectedChapter) {
                return '';
            }

            const count = this.verses.length;

            return count === 1
                ? '1 versículo disponible'
                : `${count} versículos disponibles`;
        },

        get verseReference() {
            if (! this.selectedBook || ! this.selectedChapter || ! this.selectedVerse) {
                return '';
            }

            return `${this.selectedBook.name} ${this.selectedChapter}:${this.selectedVerse.number}`;
        },

        openBookPicker() {
            this.bookOpen = true;
            this.bookQuery = '';
        },

        selectBook(book) {
            this.selectedBook = book;
            this.bookQuery = book.name;
            this.bookOpen = false;
            this.selectedChapter = '';
            this.chapters = Array.from({ length: book.chapters }, (_, index) => index + 1);
            this.verses = [];
            this.selectedVerse = null;
            this.verseQuery = '';
            this.chapterError = null;
        },

        onBookInput() {
            this.bookOpen = true;

            if (this.selectedBook && this.bookQuery !== this.selectedBook.name) {
                this.selectedBook = null;
                this.selectedChapter = '';
                this.chapters = [];
                this.verses = [];
                this.selectedVerse = null;
            }
        },

        async selectChapter(chapter, options = {}) {
            if (! this.selectedBook) {
                return;
            }

            this.selectedChapter = chapter;
            this.loadingChapter = true;
            this.chapterError = null;
            this.selectedVerse = null;
            this.verseQuery = '';

            const url = config.chapterUrl
                .replace('__BOOK__', encodeURIComponent(this.selectedBook.abbr))
                .replace('__CHAPTER__', String(chapter));

            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                });

                if (! response.ok) {
                    throw new Error('chapter');
                }

                const data = await response.json();
                this.verses = data.verses ?? [];

                if (! options.skipSave) {
                    await this.saveProgress();
                }
            } catch {
                this.verses = [];
                this.chapterError = 'No se pudo cargar este capítulo.';
            } finally {
                this.loadingChapter = false;
            }
        },

        openVerse(verse, options = {}) {
            this.selectedVerse = verse;

            if (! options.skipSave) {
                this.saveProgress(verse);
            }

            this.$nextTick(() => {
                this.$refs.verseDetail?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        },

        closeVerse() {
            this.selectedVerse = null;
        },

        truncate(text, limit = 120) {
            if (! text || text.length <= limit) {
                return text;
            }

            return `${text.slice(0, limit).trim()}…`;
        },

        async copyVerse() {
            if (! this.selectedVerse) {
                return;
            }

            const text = `${this.verseReference}\n\n${this.selectedVerse.text}\n\n${this.selectedVerse.explanation}`;

            try {
                await navigator.clipboard.writeText(text);
            } catch {
                // Navegador sem permissão de clipboard.
            }
        },

        async shareVerse() {
            if (! this.selectedVerse) {
                return;
            }

            const text = `${this.verseReference}\n\n${this.selectedVerse.text}`;

            if (navigator.share) {
                try {
                    await navigator.share({
                        title: this.verseReference,
                        text,
                    });
                } catch {
                    // Usuario canceló o share no es compatible.
                }

                return;
            }

            await this.copyVerse();
        },
    }));
});
