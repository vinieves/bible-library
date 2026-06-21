<?php

namespace App\Models;

use App\Enums\EvolutionRegistryEventDirection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvolutionRegistryEvent extends Model
{
    protected $fillable = [
        'registry_contact_id',
        'evolution_webhook_log_id',
        'event',
        'instance_name',
        'phone_normalized',
        'remote_jid',
        'direction',
        'contact_name',
        'summary',
        'message_preview',
        'from_me',
        'route_slug',
        'flow_triggered',
        'occurred_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'direction' => EvolutionRegistryEventDirection::class,
            'from_me' => 'boolean',
            'flow_triggered' => 'boolean',
            'occurred_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(EvolutionRegistryContact::class, 'registry_contact_id');
    }

    public function webhookLog(): BelongsTo
    {
        return $this->belongsTo(EvolutionWebhookLog::class, 'evolution_webhook_log_id');
    }
}
