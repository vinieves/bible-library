<?php

namespace App\Services;

class VerseOfTheDayService
{
    public function __construct(private BibleReaderService $bible) {}

    /**
     * @return array{reference: string, text: string}|null
     */
    public function today(): ?array
    {
        if (! $this->bible->isAvailable()) {
            return null;
        }

        $list = config('verse_of_the_day.verses', []);

        if ($list === []) {
            return null;
        }

        $index = (int) date('z') % count($list);
        $entry = $list[$index];

        $chapterData = $this->bible->chapter($entry['book'], $entry['chapter']);

        if ($chapterData === null) {
            return null;
        }

        $text = [];

        foreach ($chapterData['verses'] as $verse) {
            if ($verse['number'] >= $entry['verse_start'] && $verse['number'] <= $entry['verse_end']) {
                $text[] = $verse['text'];
            }
        }

        if ($text === []) {
            return null;
        }

        $bookName = $chapterData['bookName'];
        $reference = $entry['verse_start'] === $entry['verse_end']
            ? "{$bookName} {$entry['chapter']}:{$entry['verse_start']}"
            : "{$bookName} {$entry['chapter']}:{$entry['verse_start']}-{$entry['verse_end']}";

        return [
            'reference' => $reference,
            'text' => implode(' ', $text),
        ];
    }
}
