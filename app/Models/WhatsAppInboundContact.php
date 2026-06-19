<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppInboundContact extends Model
{
    protected $table = 'whatsapp_inbound_contacts';

    protected $fillable = [
        'phone_normalized',
        'remote_jid',
        'push_name',
        'first_message_at',
        'first_message_id',
        'flow_execution_id',
    ];

    protected function casts(): array
    {
        return [
            'first_message_at' => 'datetime',
        ];
    }

    public function flowExecution(): BelongsTo
    {
        return $this->belongsTo(WhatsAppFlowExecution::class, 'flow_execution_id');
    }
}
