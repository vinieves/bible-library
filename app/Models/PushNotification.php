<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PushNotification extends Model
{
    protected $fillable = [
        'title',
        'body',
        'url',
        'icon',
        'schedule_type',
        'scheduled_at',
        'recurrence_frequency',
        'recurrence_time',
        'recurrence_days',
        'status',
        'last_sent_at',
        'sent_count',
        'failed_count',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'recurrence_days' => 'array',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Payload enviado ao navegador via web push.
     *
     * @return array<string, mixed>
     */
    public function toPushPayload(): array
    {
        return array_filter([
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'icon' => $this->iconUrl(),
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * Resolve o ícone para uma URL absoluta. Aceita tanto um caminho de arquivo
     * enviado (disco public) quanto uma URL http(s) já completa (registros antigos).
     */
    public function iconUrl(): ?string
    {
        $icon = $this->icon;

        if (blank($icon)) {
            return null;
        }

        if (str_starts_with($icon, 'http://') || str_starts_with($icon, 'https://')) {
            return $icon;
        }

        return url(Storage::disk('public')->url($icon));
    }

    /**
     * A notificação deve ser disparada neste instante? (usado pelo scheduler)
     */
    public function isDue(CarbonInterface $now): bool
    {
        if ($this->status !== 'scheduled') {
            return false;
        }

        return match ($this->schedule_type) {
            'once' => $this->scheduled_at !== null && $now->greaterThanOrEqualTo($this->scheduled_at),
            'recurring' => $this->isRecurringDue($now),
            default => false,
        };
    }

    protected function isRecurringDue(CarbonInterface $now): bool
    {
        if (blank($this->recurrence_time)) {
            return false;
        }

        // Dia da semana (0=Dom .. 6=Sáb) precisa bater quando for semanal.
        if ($this->recurrence_frequency === 'weekly') {
            $days = array_map('intval', $this->recurrence_days ?? []);

            if (! in_array((int) $now->dayOfWeek, $days, true)) {
                return false;
            }
        }

        $target = $this->occurrenceTarget($now);

        if ($now->lessThan($target)) {
            return false;
        }

        // Ainda não disparou nesta ocorrência de hoje.
        return $this->last_sent_at === null || $this->last_sent_at->lessThan($target);
    }

    /**
     * Horário-alvo da ocorrência de hoje (data de $now + recurrence_time).
     */
    protected function occurrenceTarget(CarbonInterface $now): CarbonInterface
    {
        [$hour, $minute] = array_pad(explode(':', (string) $this->recurrence_time), 2, '0');

        return $now->copy()->setTime((int) $hour, (int) $minute, 0);
    }

    /**
     * Marca a notificação após um disparo. Uma-vez encerra; recorrente continua.
     */
    public function markDispatched(): void
    {
        $this->last_sent_at = now();

        if ($this->schedule_type !== 'recurring') {
            $this->status = 'sent';
        }

        $this->save();
    }
}
