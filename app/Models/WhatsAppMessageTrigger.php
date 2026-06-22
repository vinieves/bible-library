<?php

namespace App\Models;

use App\Support\MessageTriggerNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WhatsAppMessageTrigger extends Model
{
    protected $fillable = [
        'public_code',
        'name',
        'message',
        'message_normalized',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function flows(): HasMany
    {
        return $this->hasMany(WhatsAppFlow::class, 'message_trigger_id');
    }

    protected static function booted(): void
    {
        static::creating(function (WhatsAppMessageTrigger $trigger): void {
            if (blank($trigger->public_code)) {
                $trigger->public_code = static::generatePublicCode();
            }

            $trigger->syncNormalizedMessage();
        });

        static::updating(function (WhatsAppMessageTrigger $trigger): void {
            if ($trigger->isDirty('message')) {
                $trigger->syncNormalizedMessage();
            }
        });
    }

    public function syncNormalizedMessage(): void
    {
        $this->message_normalized = MessageTriggerNormalizer::normalize($this->message) ?? '';
    }

    public static function generatePublicCode(): string
    {
        do {
            $code = 'GAT-'.Str::upper(Str::random(8));
        } while (static::query()->where('public_code', $code)->exists());

        return $code;
    }
}
