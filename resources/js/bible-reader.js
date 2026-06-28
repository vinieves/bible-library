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
        searchLoading: false,
        searchError: null,
        searchTimer: null,
        searchResults: null,
        topics: [],
        topicsLoading: true,
        activeTopicId: null,

        async init() {
            this.loadTopics();

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

        onSearchInput() {
            clearTimeout(this.searchTimer);

            const query = this.bookQuery.trim();

            if (! query) {
                this.searchResults = null;
                this.searchError = null;
                this.activeTopicId = null;
                return;
            }

            this.searchTimer = setTimeout(() => this.runSearch(query), 350);
        },

        async runSearch(query) {
            if (! config.searchUrl) {
                return;
            }

            this.searchLoading = true;
            this.searchError = null;
            this.activeTopicId = null;

            try {
                const url = `${config.searchUrl}?q=${encodeURIComponent(query)}`;
                const response = await fetch(url, { headers: { Accept: 'application/json' } });

                if (! response.ok) {
                    throw new Error('search');
                }

                const data = await response.json();

                if (data.type === 'reference') {
                    this.searchResults = null;
                    await this.jumpToReference(data);
                } else {
                    this.searchResults = data;
                    this.bookOpen = false;
                }
            } catch {
                this.searchError = 'No se pudo completar la búsqueda.';
            } finally {
                this.searchLoading = false;
            }
        },

        async jumpToReference({ book, chapter, verse }) {
            const matchedBook = this.books.find((item) => item.abbr === book.abbr);

            if (! matchedBook) {
                return;
            }

            this.selectBook(matchedBook);
            this.bookQuery = matchedBook.name;

            if (! chapter) {
                return;
            }

            await this.selectChapter(chapter);

            if (! verse) {
                return;
            }

            const verseRow = this.verses.find((item) => item.number === verse);

            if (verseRow) {
                this.openVerse(verseRow);
            }
        },

        async openSearchMatch(match) {
            const needsChapterLoad = ! this.selectedBook
                || this.selectedBook.abbr !== match.book_abbr
                || Number(this.selectedChapter) !== match.chapter;

            if (needsChapterLoad) {
                const matchedBook = this.books.find((item) => item.abbr === match.book_abbr);

                if (! matchedBook) {
                    return;
                }

                this.selectBook(matchedBook);
                this.bookQuery = matchedBook.name;
                await this.selectChapter(match.chapter);
            }

            const verseRow = this.verses.find((item) => item.number === match.verse);

            if (verseRow) {
                this.openVerse(verseRow);
                this.searchResults = null;
                this.bookQuery = '';
            }
        },

        async loadTopics() {
            if (! config.topicsUrl) {
                this.topicsLoading = false;
                return;
            }

            try {
                const response = await fetch(config.topicsUrl, { headers: { Accept: 'application/json' } });
                this.topics = response.ok ? await response.json() : [];
            } catch {
                this.topics = [];
            } finally {
                this.topicsLoading = false;
            }
        },

        async selectTopic(topic) {
            if (! config.topicUrlTemplate) {
                return;
            }

            this.searchLoading = true;
            this.searchError = null;
            this.bookQuery = '';
            this.bookOpen = false;
            this.activeTopicId = topic.id;

            try {
                const url = config.topicUrlTemplate.replace('__TOPIC__', encodeURIComponent(topic.id));
                const response = await fetch(url, { headers: { Accept: 'application/json' } });

                if (! response.ok) {
                    throw new Error('topic');
                }

                this.searchResults = await response.json();
            } catch {
                this.searchError = 'No se pudo cargar este tópico.';
            } finally {
                this.searchLoading = false;
            }
        },

        clearSearch() {
            this.bookQuery = '';
            this.searchResults = null;
            this.searchError = null;
            this.activeTopicId = null;
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
