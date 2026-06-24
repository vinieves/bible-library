<?php

namespace App\Models;

use App\Services\BibleReaderService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBibleProgress extends Model
{
    protected $table = 'user_bible_progress';

    protected $attributes = [
        'monthly_verses_read' => 0,
    ];

    protected $fillable = [
        'user_id',
        'book_abbr',
        'chapter',
        'verse',
        'monthly_verses_read',
        'monthly_period',
    ];

    protected function casts(): array
    {
        return [
            'chapter' => 'integer',
            'verse' => 'integer',
            'monthly_verses_read' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookName(): string
    {
        return (string) config("bible.books.{$this->book_abbr}", $this->book_abbr);
    }

    public function chapterCount(): int
    {
        $books = app(BibleReaderService::class)->booksIndex();

        foreach ($books as $book) {
            if ($book['abbr'] === $this->book_abbr) {
                return (int) $book['chapters'];
            }
        }

        return 0;
    }

    public function completionPercent(): int
    {
        $totalChapters = $this->chapterCount();

        if ($totalChapters <= 0 || $this->chapter < 1) {
            return 0;
        }

        if ($this->chapter >= $totalChapters) {
            return 99;
        }

        return max(1, min(99, (int) round((($this->chapter - 1) / $totalChapters) * 100)));
    }

    public function statusLabel(): string
    {
        return $this->chapterLabel().' · '.$this->completionPercent().'%';
    }

    public function chapterLabel(): string
    {
        $label = "{$this->bookName()} · Capítulo {$this->chapter}";

        if ($this->verse) {
            $label .= ":{$this->verse}";
        }

        return $label;
    }

    public function isInProgress(): bool
    {
        return $this->chapter > 0 && $this->completionPercent() < 100;
    }
}
