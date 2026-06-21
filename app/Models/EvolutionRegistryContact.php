<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvolutionRegistryContact extends Model
{
    protected $fillable = [
        'phone_normalized',
        'instance_name',
        'contact_name',
        'remote_jid',
        'events_count',
        'inbound_count',
        'outbound_count',
        'flow_executions_count',
        'has_inbound_contact',
        'first_seen_at',
        'last_event_at',
        'last_inbound_at',
        'last_outbound_at',
        'last_message_preview',
    ];

    protected function casts(): array
    {
        return [
            'events_count' => 'integer',
            'inbound_count' => 'integer',
            'outbound_count' => 'integer',
            'flow_executions_count' => 'integer',
            'has_inbound_contact' => 'boolean',
            'first_seen_at' => 'datetime',
            'last_event_at' => 'datetime',
            'last_inbound_at' => 'datetime',
            'last_outbound_at' => 'datetime',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(EvolutionRegistryEvent::class, 'registry_contact_id')
            ->orderByDesc('occurred_at');
    }
}
