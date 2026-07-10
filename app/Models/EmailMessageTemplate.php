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
        'inline_images',
        'attachments',
        'is_enabled',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'event' => WhatsAppMessageEvent::class,
            'inline_images' => 'array',
            'attachments' => 'array',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
