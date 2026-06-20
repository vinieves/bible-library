<?php

namespace App\Models;

use App\Enums\WhatsAppFlowStepType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppFlowStep extends Model
{
    protected $table = 'whatsapp_flow_steps';

    protected $fillable = [
        'flow_id',
        'order',
        'type',
        'content',
        'caption',
        'file_name',
        'media_url',
        'buttons',
        'delay_seconds',
        'typing_delay',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'type' => WhatsAppFlowStepType::class,
            'buttons' => 'array',
            'delay_seconds' => 'integer',
            'typing_delay' => 'integer',
        ];
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(WhatsAppFlow::class, 'flow_id');
    }
}
