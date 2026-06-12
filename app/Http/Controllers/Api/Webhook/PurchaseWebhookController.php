<?php

namespace App\Http\Controllers\Api\Webhook;

use App\Enums\WebhookPlatform;
use App\Exceptions\WebhookProcessingException;
use App\Http\Controllers\Controller;
use App\Services\PurchaseWebhookService;
use App\Services\Webhooks\WebhookAdapterResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class PurchaseWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $platform,
        WebhookAdapterResolver $adapterResolver,
        PurchaseWebhookService $purchaseWebhookService,
    ): JsonResponse {
        try {
            $resolvedPlatform = WebhookPlatform::tryFromRoute($platform);

            if (! $resolvedPlatform) {
                return response()->json([
                    'message' => 'Plataforma de webhook não suportada.',
                ], 404);
            }

            $parsed = $adapterResolver->resolve($resolvedPlatform)->parse($request);

            if ($parsed->ignored) {
                return response()->json([
                    'status' => 'ignored',
                    'message' => $parsed->reason,
                ]);
            }

            $result = $purchaseWebhookService->process($parsed->data, $resolvedPlatform);

            return response()->json($result);
        } catch (WebhookProcessingException $exception) {
            Log::warning('Webhook de compra rejeitado.', [
                'platform' => $platform,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 422);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 404);
        } catch (Throwable $exception) {
            Log::error('Erro inesperado no webhook de compra.', [
                'platform' => $platform,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro interno ao processar webhook.',
            ], 500);
        }
    }
}
