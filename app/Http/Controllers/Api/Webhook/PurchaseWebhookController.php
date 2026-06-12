<?php

namespace App\Http\Controllers\Api\Webhook;

use App\Enums\WebhookPlatform;
use App\Http\Controllers\Controller;
use App\Services\Webhooks\IncomingWebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PurchaseWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $platform,
        IncomingWebhookProcessor $processor,
    ): JsonResponse {
        try {
            $resolvedPlatform = WebhookPlatform::tryFromRoute($platform);

            if (! $resolvedPlatform) {
                return response()->json([
                    'message' => 'Plataforma de webhook não suportada.',
                ], 404);
            }

            $result = $processor->handle($request, $resolvedPlatform);

            return response()->json($result['body'], $result['http_status']);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 404);
        }
    }
}
