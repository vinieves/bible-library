<?php

namespace App\Models;

use App\Enums\WhatsAppFlowExecutionLogStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppFlowExecutionLog extends Model
{
    protected $fillable = [
        'execution_id',
        'step_id',
        'step_order',
        'step_type',
        'status',
        'http_status',
        'error_message',
        'evolution_response',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => WhatsAppFlowExecutionLogStatus::class,
            'evolution_response' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(WhatsAppFlowExecution::class, 'execution_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(WhatsAppFlowStep::class, 'step_id');
    }
}
