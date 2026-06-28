<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BibleTopicVerse extends Model
{
    protected $fillable = [
        'bible_topic_id',
        'book_abbr',
        'chapter',
        'verse',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'chapter' => 'integer',
            'verse' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(BibleTopic::class, 'bible_topic_id');
    }
}
