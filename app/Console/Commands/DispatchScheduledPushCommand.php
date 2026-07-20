<?php

namespace App\Console\Commands;

use App\Models\PushNotification;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;

class DispatchScheduledPushCommand extends Command
{
    protected $signature = 'push:dispatch-scheduled';

    protected $description = 'Dispara notificações push agendadas (data única) e recorrentes que estão no horário.';

    public function handle(PushNotificationService $service): int
    {
        $now = now();

        $candidates = PushNotification::query()
            ->where('status', 'scheduled')
            ->whereIn('schedule_type', ['once', 'recurring'])
            ->get();

        $dispatched = 0;

        foreach ($candidates as $notification) {
            if (! $notification->isDue($now)) {
                continue;
            }

            $service->dispatch($notification);
            $dispatched++;

            $this->info("Disparada: #{$notification->id} — {$notification->title}");
        }

        if ($dispatched === 0) {
            $this->info('Nenhuma notificação no horário.');
        }

        return self::SUCCESS;
    }
}
