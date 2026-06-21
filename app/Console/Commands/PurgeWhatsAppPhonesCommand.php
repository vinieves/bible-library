<?php

namespace App\Console\Commands;

use App\Models\EvolutionRegistryContact;
use App\Models\EvolutionRegistryEvent;
use App\Models\EvolutionWebhookLog;
use App\Models\WhatsAppDispatchLog;
use App\Models\WhatsAppFlowExecution;
use App\Models\WhatsAppInboundContact;
use App\Models\WhatsAppPendingInboundResponse;
use App\Services\Webhooks\PhoneNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurgeWhatsAppPhonesCommand extends Command
{
    protected $signature = 'whatsapp:purge-phones
                            {phones* : Um ou mais telefones (ex.: 553388138635)}';

    protected $description = 'Remove todos os registros dos telefones informados (fluxos, primeira mensagem, registro Evolution, webhooks e fila pendente).';

    public function handle(): int
    {
        $variants = $this->resolveAllVariants($this->argument('phones'));

        if ($variants === []) {
            $this->error('Nenhum telefone válido informado.');

            return self::FAILURE;
        }

        $this->info('Variantes a limpar: '.implode(', ', $variants));

        $executionIds = WhatsAppFlowExecution::query()
            ->whereIn('phone_normalized', $variants)
            ->pluck('id');

        $counts = DB::transaction(function () use ($variants, $executionIds): array {
            $jobsDeleted = $this->purgeQueuedFlowJobs($executionIds->all());

            $inbound = $this->deleteIfTable('whatsapp_inbound_contacts', fn () => WhatsAppInboundContact::query()
                ->whereIn('phone_normalized', $variants)
                ->delete());

            $pending = $this->deleteIfTable('whatsapp_pending_inbound_responses', fn () => WhatsAppPendingInboundResponse::query()
                ->whereIn('phone_normalized', $variants)
                ->delete());

            $registryContacts = $this->deleteIfTable('evolution_registry_contacts', fn () => EvolutionRegistryContact::query()
                ->whereIn('phone_normalized', $variants)
                ->delete());

            $registryEventsOrphans = $this->deleteIfTable('evolution_registry_events', fn () => EvolutionRegistryEvent::query()
                ->whereIn('phone_normalized', $variants)
                ->delete());

            $executions = $this->deleteIfTable('whatsapp_flow_executions', fn () => WhatsAppFlowExecution::query()
                ->whereIn('phone_normalized', $variants)
                ->delete());

            $webhooks = $this->deleteIfTable('evolution_webhook_logs', fn () => EvolutionWebhookLog::query()
                ->whereIn('phone_normalized', $variants)
                ->delete());

            $dispatches = $this->deleteIfTable('whatsapp_dispatch_logs', fn () => WhatsAppDispatchLog::query()
                ->where(function ($query) use ($variants): void {
                    $query->whereIn('phone_normalized', $variants)
                        ->orWhereIn('phone', $variants);
                })
                ->delete());

            return compact(
                'jobsDeleted',
                'inbound',
                'pending',
                'registryContacts',
                'registryEventsOrphans',
                'executions',
                'webhooks',
                'dispatches',
            );
        });

        $this->table(
            ['Registro', 'Removidos'],
            [
                ['Jobs de fluxo na fila', (string) $counts['jobsDeleted']],
                ['Primeira mensagem (inbound contacts)', (string) $counts['inbound']],
                ['Respostas pendentes (wait step)', (string) $counts['pending']],
                ['Registro Geral (contatos)', (string) $counts['registryContacts']],
                ['Registro Geral (eventos)', (string) $counts['registryEventsOrphans']],
                ['Execuções de fluxo (+ logs de passo)', (string) $counts['executions']],
                ['Webhooks Evolution', (string) $counts['webhooks']],
                ['Disparos WhatsApp', (string) $counts['dispatches']],
            ],
        );

        $this->newLine();
        $this->info('Pronto. Envie a primeira mensagem novamente para testar o funil.');

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $phones
     * @return list<string>
     */
    private function resolveAllVariants(array $phones): array
    {
        $variants = [];

        foreach ($phones as $phone) {
            $variants = array_merge($variants, PhoneNumber::matchVariants($phone));
        }

        return array_values(array_unique(array_filter($variants)));
    }

    /**
     * @param  list<int>  $executionIds
     */
    private function purgeQueuedFlowJobs(array $executionIds): int
    {
        if ($executionIds === [] || ! DB::getSchemaBuilder()->hasTable('jobs')) {
            return 0;
        }

        $deleted = 0;

        foreach ($executionIds as $executionId) {
            $deleted += DB::table('jobs')
                ->where('payload', 'like', '%ExecuteWhatsAppFlowJob%')
                ->where('payload', 'like', '%'.$executionId.'%')
                ->delete();
        }

        return $deleted;
    }

    private function deleteIfTable(string $table, callable $callback): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) $callback();
    }
}
