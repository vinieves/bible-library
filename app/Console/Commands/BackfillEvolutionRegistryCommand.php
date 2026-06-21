<?php

namespace App\Console\Commands;

use App\Models\EvolutionRegistryEvent;
use App\Models\EvolutionWebhookLog;
use App\Services\EvolutionRegistryService;
use Illuminate\Console\Command;

class BackfillEvolutionRegistryCommand extends Command
{
    protected $signature = 'evolution:backfill-registry {--from= : ID inicial do webhook log}';

    protected $description = 'Popula o Registro Geral a partir dos webhooks Evolution já armazenados.';

    public function handle(EvolutionRegistryService $registryService): int
    {
        $fromId = (int) ($this->option('from') ?: 0);
        $processed = 0;
        $skipped = 0;

        EvolutionWebhookLog::query()
            ->when($fromId > 0, fn ($query) => $query->where('id', '>=', $fromId))
            ->orderBy('id')
            ->chunkById(200, function ($logs) use ($registryService, &$processed, &$skipped): void {
                foreach ($logs as $log) {
                    $alreadyExists = EvolutionRegistryEvent::query()
                        ->where('evolution_webhook_log_id', $log->id)
                        ->exists();

                    $registryService->recordFromWebhookLog($log);

                    if ($alreadyExists) {
                        $skipped++;
                    } else {
                        $processed++;
                    }
                }
            });

        $this->info("Registro criado para {$processed} webhooks ({$skipped} já existiam).");

        return self::SUCCESS;
    }
}
