<?php

namespace App\Models;

use App\Enums\EmailBroadcastStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailBroadcast extends Model
{
    protected $fillable = [
        'subject',
        'body',
        'audience_type',
        'audience_segment',
        'email_list',
        'exclude_admins',
        'status',
        'total_recipients',
        'sent_count',
        'failed_count',
        'batch_id',
        'created_by',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => EmailBroadcastStatus::class,
            'email_list' => 'array',
            'exclude_admins' => 'boolean',
            'total_recipients' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === EmailBroadcastStatus::Draft;
    }

    public function audienceLabel(): string
    {
        return match ($this->audience_type) {
            'login_segment' => 'Situação: '.match ($this->audience_segment) {
                'dormant' => 'Sumidos (30d+)',
                'never' => 'Nunca logaram',
                'active7' => 'Ativos (7d)',
                'new7' => 'Novos (7d)',
                default => 'Todos',
            },
            'email_list' => 'Lista colada ('.count($this->email_list ?? []).' e-mails)',
            default => 'Todos os registrados',
        };
    }
}
