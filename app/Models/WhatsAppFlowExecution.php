<?php

namespace App\Models;

use App\Enums\WhatsAppFlowExecutionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppFlowExecution extends Model
{
    protected $table = 'whatsapp_flow_executions';

    protected $fillable = [
        'flow_id',
        'phone',
        'phone_normalized',
        'user_id',
        'trigger',
        'status',
        'current_step',
        'total_steps',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'status' => WhatsAppFlowExecutionStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'current_step' => 'integer',
            'total_steps' => 'integer',
        ];
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(WhatsAppFlow::class, 'flow_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WhatsAppFlowExecutionLog::class, 'execution_id');
    }
}
