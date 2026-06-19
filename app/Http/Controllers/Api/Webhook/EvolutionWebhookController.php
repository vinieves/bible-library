<?php

namespace App\Http\Controllers\Api\Webhook;

use App\Jobs\ProcessEvolutionInboundMessageJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EvolutionWebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        Log::info('Webhook Evolution recebido.', [
            'event' => $request->input('event'),
            'instance' => $request->input('instance'),
            'remote_jid' => data_get($request->all(), 'data.key.remoteJid'),
            'from_me' => data_get($request->all(), 'data.key.fromMe'),
            'has_apikey' => filled($request->input('apikey') ?? $request->header('apikey')),
        ]);

        ProcessEvolutionInboundMessageJob::dispatch($request->all());

        return response()->json([
            'status' => 'accepted',
        ]);
    }
}
