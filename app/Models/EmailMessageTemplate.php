<?php

namespace App\Models;

use App\Enums\WhatsAppMessageEvent;
use Illuminate\Database\Eloquent\Model;

class EmailMessageTemplate extends Model
{
    protected $fillable = [
        'event',
        'subject',
        'body',
        'is_enabled',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'event' => WhatsAppMessageEvent::class,
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
