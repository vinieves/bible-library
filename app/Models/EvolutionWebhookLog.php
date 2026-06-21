<?php

namespace App\Models;

use App\Enums\EvolutionWebhookLogStatus;
use Illuminate\Database\Eloquent\Model;

class EvolutionWebhookLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event',
        'instance',
        'route_slug',
        'phone_normalized',
        'remote_jid',
        'from_me',
        'message_preview',
        'inbound_count',
        'processing_status',
        'processing_message',
        'payload',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'from_me' => 'boolean',
            'inbound_count' => 'integer',
            'processing_status' => EvolutionWebhookLogStatus::class,
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
