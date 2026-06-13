<?php

namespace App\Models;

use App\Enums\WhatsAppDispatchStatus;
use App\Enums\WhatsAppDispatchTrigger;
use App\Enums\WhatsAppMessageEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppDispatchLog extends Model
{
    protected $table = 'whatsapp_dispatch_logs';

    public $timestamps = false;

    protected $fillable = [
        'trigger',
        'message_event',
        'status',
        'phone',
        'phone_normalized',
        'user_id',
        'purchase_id',
        'hotmart_transaction',
        'message',
        'error_message',
        'http_status',
        'attempt',
        'evolution_response',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'trigger' => WhatsAppDispatchTrigger::class,
            'message_event' => WhatsAppMessageEvent::class,
            'status' => WhatsAppDispatchStatus::class,
            'evolution_response' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
