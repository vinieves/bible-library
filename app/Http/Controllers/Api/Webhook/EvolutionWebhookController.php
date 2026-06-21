<?php

namespace App\Http\Controllers\Api\Webhook;

use App\Jobs\ProcessEvolutionInboundMessageJob;
use App\Services\EvolutionWebhookLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EvolutionWebhookController
{
    public function __invoke(Request $request, EvolutionWebhookLogService $logService): JsonResponse
    {
        $log = $logService->recordIncoming($request, $request->route('eventSlug'));

        Log::info('Webhook Evolution recebido.', [
            'log_id' => $log->id,
            'event' => $request->input('event'),
            'event_slug' => $request->route('eventSlug'),
            'instance' => $request->input('instance'),
            'remote_jid' => data_get($request->all(), 'data.key.remoteJid') ?? data_get($request->all(), 'data.0.key.remoteJid'),
            'from_me' => data_get($request->all(), 'data.key.fromMe') ?? data_get($request->all(), 'data.0.key.fromMe'),
            'data_is_list' => is_array($request->input('data')) && array_is_list($request->input('data')),
            'has_apikey' => filled($request->input('apikey') ?? $request->header('apikey')),
        ]);

        ProcessEvolutionInboundMessageJob::dispatch($request->all(), $log->id);

        return response()->json([
            'status' => 'accepted',
        ]);
    }
}
