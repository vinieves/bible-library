<?php

namespace App\Services\Webhooks;

use App\Enums\WebhookLogStatus;
use App\Enums\WebhookPlatform;
use App\Exceptions\WebhookProcessingException;
use App\Models\WebhookLog;
use App\Services\PurchaseWebhookService;
use App\Services\WebhookLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class IncomingWebhookProcessor
{
    public function __construct(
        private readonly WebhookAdapterResolver $adapterResolver,
        private readonly PurchaseWebhookService $purchaseWebhookService,
        private readonly WebhookLogService $webhookLogService,
    ) {}

    /**
     * @return array{http_status: int, body: array<string, mixed>, log: WebhookLog}
     */
    public function handle(Request $request, WebhookPlatform $platform): array
    {
        try {
            $parsed = $this->adapterResolver->resolve($platform)->parse($request);

            if ($parsed->ignored) {
                $response = [
                    'status' => 'ignored',
                    'message' => $parsed->reason,
                ];

                $log = $this->webhookLogService->log(
                    request: $request,
                    platform: $platform,
                    status: WebhookLogStatus::Ignored,
                    message: $parsed->reason,
                    httpStatus: 200,
                    response: $response,
                );

                return [
                    'http_status' => 200,
                    'body' => $response,
                    'log' => $log,
                ];
            }

            $result = $this->purchaseWebhookService->process($parsed->data, $platform);

            $status = match ($result['status'] ?? '') {
                'duplicate' => WebhookLogStatus::Duplicate,
                'acknowledged' => WebhookLogStatus::Acknowledged,
                default => WebhookLogStatus::Processed,
            };

            $log = $this->webhookLogService->log(
                request: $request,
                platform: $platform,
                status: $status,
                message: $result['message'] ?? null,
                httpStatus: 200,
                response: $result,
                purchaseId: $result['purchase_id'] ?? null,
            );

            return [
                'http_status' => 200,
                'body' => $result,
                'log' => $log,
            ];
        } catch (WebhookProcessingException $exception) {
            Log::warning('Webhook de compra rejeitado.', [
                'platform' => $platform->value,
                'message' => $exception->getMessage(),
            ]);

            $response = [
                'status' => 'error',
                'message' => $exception->getMessage(),
            ];

            $log = $this->webhookLogService->log(
                request: $request,
                platform: $platform,
                status: WebhookLogStatus::Error,
                message: $exception->getMessage(),
                httpStatus: 422,
                response: $response,
            );

            return [
                'http_status' => 422,
                'body' => $response,
                'log' => $log,
            ];
        } catch (Throwable $exception) {
            Log::error('Erro inesperado no webhook de compra.', [
                'platform' => $platform->value,
                'message' => $exception->getMessage(),
            ]);

            $response = [
                'status' => 'error',
                'message' => 'Erro interno ao processar webhook.',
            ];

            $log = $this->webhookLogService->log(
                request: $request,
                platform: $platform,
                status: WebhookLogStatus::Error,
                message: $exception->getMessage(),
                httpStatus: 500,
                response: $response,
            );

            return [
                'http_status' => 500,
                'body' => $response,
                'log' => $log,
            ];
        }
    }
}
