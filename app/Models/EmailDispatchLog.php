<?php

namespace App\Models;

use App\Enums\EmailDispatchStatus;
use App\Enums\EmailDispatchTrigger;
use App\Enums\WhatsAppMessageEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDispatchLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'trigger',
        'message_event',
        'status',
        'from_address',
        'recipient_email',
        'user_id',
        'purchase_id',
        'hotmart_transaction',
        'subject',
        'body',
        'error_message',
        'attempt',
        'mailer_response',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'trigger' => EmailDispatchTrigger::class,
            'message_event' => WhatsAppMessageEvent::class,
            'status' => EmailDispatchStatus::class,
            'mailer_response' => 'array',
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
