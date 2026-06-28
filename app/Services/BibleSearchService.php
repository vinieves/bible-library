<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BibleSearchService
{
    private const FLAT_CACHE_KEY = 'bible.catholic.73.flat_index';

    private const RESULT_LIMIT = 50;

    public function __construct(private BibleReaderService $bible)
    {
    }

    public function search(string $query): array
    {
        $query = trim($query);

        if ($query === '') {
            return ['type' => 'results', 'query' => $query, 'matches' => []];
        }

        $reference = $this->parseReference($query);

        if ($reference !== null) {
            return [
                'type' => 'reference',
                'book' => $reference['book'],
                'chapter' => $reference['chapter'],
                'verse' => $reference['verse'],
            ];
        }

        return $this->searchContent($query);
    }

    /**
     * @return array{book: array{abbr: string, name: string}, chapter: ?int, verse: ?int}|null
     */
    public function parseReference(string $query): ?array
    {
        $query = trim($query);

        if (! preg_match('/^(.*?)(?:\s+(\d+)(?:[:.,]\s*(\d+))?)?\s*$/u', $query, $matches)) {
            return null;
        }

        $candidate = trim($matches[1] ?? '');

        if ($candidate === '') {
            return null;
        }

        $book = $this->matchBook($candidate);

        if ($book === null) {
            return null;
        }

        return [
            'book' => $book,
            'chapter' => isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : null,
            'verse' => isset($matches[3]) && $matches[3] !== '' ? (int) $matches[3] : null,
        ];
    }

    /**
     * @return array{abbr: string, name: string}|null
     */
    private function matchBook(string $candidate): ?array
    {
        $normalizedCandidate = Str::ascii(mb_strtolower($candidate));
        $catalog = config('bible.books', []);

        foreach ($catalog as $abbr => $name) {
            $normalizedName = Str::ascii(mb_strtolower($name));
            $normalizedAbbr = Str::ascii(mb_strtolower($abbr));

            if ($normalizedCandidate === $normalizedName || $normalizedCandidate === $normalizedAbbr) {
                return ['abbr' => $abbr, 'name' => $name];
            }
        }

        $startsWith = [];

        foreach ($catalog as $abbr => $name) {
            $normalizedName = Str::ascii(mb_strtolower($name));
            $normalizedAbbr = Str::ascii(mb_strtolower($abbr));

            if (str_starts_with($normalizedName, $normalizedCandidate) || str_starts_with($normalizedAbbr, $normalizedCandidate)) {
                $startsWith[] = ['abbr' => $abbr, 'name' => $name];
            }
        }

        if (count($startsWith) === 0) {
            return null;
        }

        usort($startsWith, fn ($a, $b) => mb_strlen($a['name']) <=> mb_strlen($b['name']));

        return $startsWith[0];
    }

    private function searchContent(string $query): array
    {
        $normalizedQuery = Str::ascii(mb_strtolower($query));
        $matches = [];

        foreach ($this->flatIndex() as $row) {
            if (count($matches) >= self::RESULT_LIMIT) {
                break;
            }

            $textHit = str_contains($row['text_normalized'], $normalizedQuery);
            $explanationHit = ! $textHit && str_contains($row['explanation_normalized'], $normalizedQuery);

            if (! $textHit && ! $explanationHit) {
                continue;
            }

            $matches[] = [
                'book_abbr' => $row['book_abbr'],
                'book_name' => $row['book_name'],
                'chapter' => $row['chapter'],
                'verse' => $row['number'],
                'snippet' => $this->snippet(
                    $textHit ? $row['text'] : $row['explanation'],
                    $textHit ? $row['text_normalized'] : $row['explanation_normalized'],
                    $normalizedQuery,
                ),
            ];
        }

        return [
            'type' => 'results',
            'query' => $query,
            'matches' => $matches,
        ];
    }

    private function snippet(string $original, string $normalized, string $normalizedQuery): string
    {
        $offset = mb_strpos($normalized, $normalizedQuery);

        if ($offset === false) {
            return Str::limit($original, 110);
        }

        $start = max(0, $offset - 50);
        $length = mb_strlen($normalizedQuery) + 100;
        $totalLength = mb_strlen($original);

        $snippet = trim(mb_substr($original, $start, $length));

        return ($start > 0 ? '…' : '').$snippet.(($start + $length) < $totalLength ? '…' : '');
    }

    /**
     * Cached forever via the `file` store specifically (not this app's default `database` store):
     * the database cache driver round-trips this ~30k-row structure through a SQL blob column on
     * every read AND write, which — combined with PHP's default 128M memory_limit — exhausted
     * memory both serializing it for storage and fetching/unserializing it back. The `file` store
     * avoids the DB round-trip entirely (direct file read/write). The memory_limit bump below is
     * kept as a safety margin around the one-time build, since `BibleReaderService::rawData()`
     * (already cached) and this flattened copy briefly coexist in memory while building.
     *
     * @return list<array{book_abbr: string, book_name: string, chapter: int, number: int, text: string, explanation: string, text_normalized: string, explanation_normalized: string}>
     */
    private function flatIndex(): array
    {
        $previousLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            return Cache::store('file')->rememberForever(self::FLAT_CACHE_KEY, function (): array {
                $catalog = config('bible.books', []);
                $flat = [];

                foreach ($this->bible->rawData() as $abbr => $chapters) {
                    $name = $catalog[$abbr] ?? $abbr;

                    foreach ($chapters as $chapterNum => $verses) {
                        foreach ($verses as $verse) {
                            $text = (string) ($verse[1] ?? '');
                            $explanation = (string) ($verse[2] ?? '');

                            $flat[] = [
                                'book_abbr' => $abbr,
                                'book_name' => $name,
                                'chapter' => (int) $chapterNum,
                                'number' => (int) ($verse[0] ?? 0),
                                'text' => $text,
                                'explanation' => $explanation,
                                'text_normalized' => Str::ascii(mb_strtolower($text)),
                                'explanation_normalized' => Str::ascii(mb_strtolower($explanation)),
                            ];
                        }
                    }
                }

                return $flat;
            });
        } finally {
            ini_set('memory_limit', $previousLimit);
        }
    }
}
