<?php

namespace App\Models;

use App\Enums\WhatsAppFlowStatus;
use App\Enums\WhatsAppFlowTriggerType;
use App\Support\IntegrationSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppFlow extends Model
{
    protected $table = 'whatsapp_flows';

    protected $fillable = [
        'name',
        'description',
        'status',
        'trigger_type',
        'trigger_event',
        'message_trigger_id',
        'is_active',
        'instance_name',
        'steps_count',
    ];

    protected function casts(): array
    {
        return [
            'status' => WhatsAppFlowStatus::class,
            'trigger_type' => WhatsAppFlowTriggerType::class,
            'is_active' => 'boolean',
            'steps_count' => 'integer',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WhatsAppFlowStep::class, 'flow_id')->orderBy('order');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(WhatsAppFlowExecution::class, 'flow_id');
    }

    public function messageTrigger(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessageTrigger::class, 'message_trigger_id');
    }

    public function resolveInstanceName(): ?string
    {
        if (filled($this->instance_name)) {
            return (string) $this->instance_name;
        }

        return IntegrationSettings::evolutionInstanceForFlows();
    }

    protected static function booted(): void
    {
        static::saving(function (WhatsAppFlow $flow): void {
            if ($flow->trigger_type === WhatsAppFlowTriggerType::MessageTrigger) {
                if (! $flow->message_trigger_id) {
                    return;
                }

                if (! $flow->is_active) {
                    return;
                }

                static::query()
                    ->where('trigger_type', WhatsAppFlowTriggerType::MessageTrigger)
                    ->where('message_trigger_id', $flow->message_trigger_id)
                    ->when($flow->exists, fn ($query) => $query->whereKeyNot($flow->id))
                    ->update(['is_active' => false]);

                return;
            }

            if ($flow->trigger_type !== WhatsAppFlowTriggerType::FirstMessage || ! $flow->is_active) {
                return;
            }

            static::query()
                ->where('trigger_type', WhatsAppFlowTriggerType::FirstMessage)
                ->when($flow->exists, fn ($query) => $query->whereKeyNot($flow->id))
                ->update(['is_active' => false]);
        });
    }
}
