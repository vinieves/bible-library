<?php

namespace App\Models;

use App\Enums\WhatsAppFlowStepType;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'buttons_message',
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
            'buttons' => 'array',
            'delay_seconds' => 'integer',
            'typing_delay' => 'integer',
        ];
    }

    protected function type(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?WhatsAppFlowStepType => filled($value)
                ? WhatsAppFlowStepType::tryFrom($value)
                : null,
            set: fn (WhatsAppFlowStepType|string|null $value): ?string => $value instanceof WhatsAppFlowStepType
                ? $value->value
                : (filled($value) ? (string) $value : null),
        );
    }

    public function rawType(): string
    {
        return (string) ($this->attributes['type'] ?? '');
    }

    public function resolvedType(): ?WhatsAppFlowStepType
    {
        return WhatsAppFlowStepType::tryFrom($this->rawType());
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(WhatsAppFlow::class, 'flow_id');
    }

    public function getButtonsMessageAttribute(): ?string
    {
        if ($this->resolvedType() !== WhatsAppFlowStepType::Buttons) {
            return null;
        }

        return self::normalizeContentValue($this->attributes['content'] ?? null);
    }

    public function setButtonsMessageAttribute(mixed $value): void
    {
        $this->attributes['buttons_message'] = $value;
    }

    public static function normalizeContentValue(mixed $content): ?string
    {
        if (blank($content)) {
            return null;
        }

        if (is_string($content)) {
            return $content;
        }

        if (is_array($content)) {
            if (($content['type'] ?? null) === 'doc') {
                return self::tiptapDocumentToText($content) ?: null;
            }

            foreach ($content as $value) {
                if (is_string($value) && filled($value)) {
                    return $value;
                }
            }

            return null;
        }

        return is_scalar($content) ? (string) $content : null;
    }

    protected static function booted(): void
    {
        static::saving(function (WhatsAppFlowStep $step): void {
            $type = $step->resolvedType();

            if ($type === WhatsAppFlowStepType::Buttons) {
                $message = $step->attributes['buttons_message'] ?? null;

                if (filled($message)) {
                    $step->content = self::normalizeContentValue($message);
                } else {
                    $step->content = self::normalizeContentValue($step->content);
                }

                unset($step->attributes['buttons_message']);
            } else {
                $step->content = self::normalizeContentValue($step->content);
                $step->buttons = null;
                unset($step->attributes['buttons_message']);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $document
     */
    private static function tiptapDocumentToText(array $document): string
    {
        $chunks = [];

        foreach ($document['content'] ?? [] as $block) {
            if (! is_array($block)) {
                continue;
            }

            $line = '';

            foreach ($block['content'] ?? [] as $inline) {
                if (! is_array($inline)) {
                    continue;
                }

                $line .= (string) ($inline['text'] ?? '');
            }

            if (filled($line)) {
                $chunks[] = $line;
            }
        }

        return trim(implode("\n", $chunks));
    }
}
