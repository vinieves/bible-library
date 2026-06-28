<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use RuntimeException;

class BibleReaderService
{
    private const CACHE_KEY = 'bible.catholic.73.data';

    /**
     * @return list<array{abbr: string, name: string, chapters: int}>
     */
    public function booksIndex(): array
    {
        $data = $this->allData();
        $catalog = config('bible.books', []);

        $books = [];

        foreach ($catalog as $abbr => $name) {
            $books[] = [
                'abbr' => $abbr,
                'name' => $name,
                'chapters' => isset($data[$abbr]) ? count($data[$abbr]) : 0,
            ];
        }

        return $books;
    }

    /**
     * @return array{book: string, bookName: string, chapter: int, verses: list<array{number: int, text: string, explanation: string}>}|null
     */
    public function chapter(string $book, int $chapter): ?array
    {
        $bookName = config("bible.books.{$book}");

        if (! $bookName || $chapter < 1) {
            return null;
        }

        $data = $this->allData();
        $chapterKey = (string) $chapter;

        if (! isset($data[$book][$chapterKey])) {
            return null;
        }

        $verses = [];

        foreach ($data[$book][$chapterKey] as $verse) {
            $verses[] = [
                'number' => (int) ($verse[0] ?? 0),
                'text' => (string) ($verse[1] ?? ''),
                'explanation' => (string) ($verse[2] ?? ''),
            ];
        }

        return [
            'book' => $book,
            'bookName' => $bookName,
            'chapter' => $chapter,
            'verses' => $verses,
        ];
    }

    /**
     * @return array<string, array<string, list<list<string>>>>
     */
    public function rawData(): array
    {
        return $this->allData();
    }

    public function isAvailable(): bool
    {
        $path = config('bible.data_path');

        return filled($path) && is_string($path) && is_readable($path);
    }

    public function dataPath(): ?string
    {
        $path = config('bible.data_path');

        return filled($path) && is_string($path) ? $path : null;
    }

    /**
     * @return array<string, array<string, list<list<string>>>>
     */
    private function allData(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $path = $this->dataPath();

            if (! $path || ! is_readable($path)) {
                throw new RuntimeException('Arquivo da Bíblia não encontrado ou ilegível.');
            }

            $decoded = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : [];
        });
    }
}
