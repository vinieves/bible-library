<?php

namespace App\Models;

use App\Enums\WebhookLogStatus;
use App\Enums\WebhookPlatform;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'platform',
        'event',
        'processing_status',
        'http_status',
        'message',
        'email',
        'product_code',
        'external_reference',
        'purchase_id',
        'payload',
        'response',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'processing_status' => WebhookLogStatus::class,
            'payload' => 'array',
            'response' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function platformLabel(): string
    {
        return WebhookPlatform::tryFrom($this->platform)?->label() ?? $this->platform;
    }
}
