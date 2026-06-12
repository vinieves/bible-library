<?php

namespace App\Http\Controllers\Api\Webhook;

use App\Enums\WebhookLogStatus;
use App\Enums\WebhookPlatform;
use App\Exceptions\WebhookProcessingException;
use App\Http\Controllers\Controller;
use App\Services\PurchaseWebhookService;
use App\Services\WebhookLogService;
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
        WebhookLogService $webhookLogService,
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
                $response = [
                    'status' => 'ignored',
                    'message' => $parsed->reason,
                ];

                $webhookLogService->log(
                    request: $request,
                    platform: $resolvedPlatform,
                    status: WebhookLogStatus::Ignored,
                    message: $parsed->reason,
                    httpStatus: 200,
                    response: $response,
                );

                return response()->json($response);
            }

            $result = $purchaseWebhookService->process($parsed->data, $resolvedPlatform);

            $status = ($result['status'] ?? '') === 'duplicate'
                ? WebhookLogStatus::Duplicate
                : WebhookLogStatus::Processed;

            $webhookLogService->log(
                request: $request,
                platform: $resolvedPlatform,
                status: $status,
                message: $result['message'] ?? null,
                httpStatus: 200,
                response: $result,
                purchaseId: $result['purchase_id'] ?? null,
            );

            return response()->json($result);
        } catch (WebhookProcessingException $exception) {
            Log::warning('Webhook de compra rejeitado.', [
                'platform' => $platform,
                'message' => $exception->getMessage(),
            ]);

            $response = [
                'status' => 'error',
                'message' => $exception->getMessage(),
            ];

            if ($resolvedPlatform = WebhookPlatform::tryFromRoute($platform)) {
                $webhookLogService->log(
                    request: $request,
                    platform: $resolvedPlatform,
                    status: WebhookLogStatus::Error,
                    message: $exception->getMessage(),
                    httpStatus: 422,
                    response: $response,
                );
            }

            return response()->json($response, 422);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 404);
        } catch (Throwable $exception) {
            Log::error('Erro inesperado no webhook de compra.', [
                'platform' => $platform,
                'message' => $exception->getMessage(),
            ]);

            $response = [
                'status' => 'error',
                'message' => 'Erro interno ao processar webhook.',
            ];

            if ($resolvedPlatform = WebhookPlatform::tryFromRoute($platform)) {
                $webhookLogService->log(
                    request: $request,
                    platform: $resolvedPlatform,
                    status: WebhookLogStatus::Error,
                    message: $exception->getMessage(),
                    httpStatus: 500,
                    response: $response,
                );
            }

            return response()->json($response, 500);
        }
    }
}
