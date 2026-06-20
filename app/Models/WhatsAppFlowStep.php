<?php

namespace App\Models;

use App\Enums\WhatsAppFlowStepType;
use App\Support\WhatsAppFlowStepMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class WhatsAppFlowStep extends Model
{
    protected $table = 'whatsapp_flow_steps';

    protected $fillable = [
        'flow_id',
        'order',
        'type',
        'content',
        'content_variation_2',
        'content_variation_3',
        'text_variation_cursor',
        'caption',
        'file_name',
        'media_url',
        'media_path',
        'delay_seconds',
        'typing_delay',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'type' => WhatsAppFlowStepType::class,
            'delay_seconds' => 'integer',
            'typing_delay' => 'integer',
            'text_variation_cursor' => 'integer',
        ];
    }

    /**
     * @return list<string>
     */
    public function filledTextVariations(): array
    {
        return array_values(array_filter([
            $this->content,
            $this->content_variation_2,
            $this->content_variation_3,
        ], fn (mixed $value): bool => filled($value)));
    }

    public function textVariationCount(): int
    {
        return count($this->filledTextVariations());
    }

    public function resolveNextTextContent(): string
    {
        return DB::transaction(function (): string {
            /** @var self $step */
            $step = static::query()->lockForUpdate()->findOrFail($this->id);

            $variations = $step->filledTextVariations();

            if ($variations === []) {
                return '';
            }

            $index = $step->text_variation_cursor % count($variations);
            $selected = $variations[$index];

            $step->update([
                'text_variation_cursor' => $step->text_variation_cursor + 1,
            ]);

            return $selected;
        });
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(WhatsAppFlow::class, 'flow_id');
    }

    public function resolveMediaPublicUrl(): ?string
    {
        return WhatsAppFlowStepMedia::publicUrl($this->media_path, $this->media_url);
    }
}
