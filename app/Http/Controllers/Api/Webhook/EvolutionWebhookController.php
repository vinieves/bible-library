<?php

namespace App\Http\Controllers\Api\Webhook;

use App\Jobs\ProcessEvolutionInboundMessageJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvolutionWebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        ProcessEvolutionInboundMessageJob::dispatch($request->all());

        return response()->json([
            'status' => 'accepted',
        ]);
    }
}
