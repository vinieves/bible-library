<?php

namespace App\Services;

use App\Enums\WhatsAppFlowStepType;
use App\Models\WhatsAppFlowStep;
use App\Support\IntegrationSettings;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhatsAppFlowStepSenderService
{
    private string $baseUrl;

    private string $instance;

    private string $apiKey;

    public function __construct()
    {
        $baseUrl = IntegrationSettings::evolutionBaseUrl();
        $instance = IntegrationSettings::evolutionInstance();
        $apiKey = IntegrationSettings::evolutionApiKey();

        if (! $baseUrl || ! $instance || ! $apiKey) {
            throw new RuntimeException('Evolution API não configurada no painel admin.');
        }

        $this->baseUrl = rtrim($baseUrl, '/');
        $this->instance = $instance;
        $this->apiKey = $apiKey;
    }

    /**
     * @return array{success: bool, http_status: int, response: array|null, error: string|null}
     */
    public function send(WhatsAppFlowStep $step, string $phoneNormalized): array
    {
        try {
            $type = $step->type instanceof WhatsAppFlowStepType
                ? $step->type
                : WhatsAppFlowStepType::tryFrom((string) $step->type);

            if (! $type) {
                return [
                    'success' => false,
                    'http_status' => 0,
                    'response' => null,
                    'error' => "Tipo de passo desconhecido: {$step->type}",
                ];
            }

            if ($type === WhatsAppFlowStepType::Delay) {
                if ($step->delay_seconds > 0) {
                    sleep($step->delay_seconds);
                }

                return ['success' => true, 'http_status' => 200, 'response' => null, 'error' => null];
            }

            if ($step->delay_seconds > 0) {
                sleep($step->delay_seconds);
            }

            return match ($type) {
                WhatsAppFlowStepType::Text => $this->sendText($step, $phoneNormalized),
                WhatsAppFlowStepType::Image,
                WhatsAppFlowStepType::Video,
                WhatsAppFlowStepType::File => $this->sendMedia($step, $phoneNormalized, $type),
                WhatsAppFlowStepType::Audio => $this->sendAudio($step, $phoneNormalized),
            };
        } catch (\Throwable $exception) {
            Log::error('WhatsAppFlowStepSenderService error', [
                'step_id' => $step->id,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'http_status' => 0,
                'response' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array{success: bool, http_status: int, response: array|null, error: string|null}
     */
    private function sendText(WhatsAppFlowStep $step, string $phone): array
    {
        $response = Http::timeout(20)
            ->withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post("{$this->baseUrl}/message/sendText/{$this->instance}", [
                'number' => $phone,
                'text' => $this->plainTextContent($step->content),
                'delay' => max(0, ($step->typing_delay ?? 3)) * 1000,
            ]);

        return $this->parseResponse($response);
    }

    /**
     * @return array{success: bool, http_status: int, response: array|null, error: string|null}
     */
    private function sendMedia(WhatsAppFlowStep $step, string $phone, WhatsAppFlowStepType $type): array
    {
        $mediaUrl = trim((string) ($step->media_url ?? $step->content ?? ''));

        if (blank($mediaUrl)) {
            return [
                'success' => false,
                'http_status' => 0,
                'response' => null,
                'error' => 'URL da mídia não informada.',
            ];
        }

        $extension = $this->resolveMediaExtension($mediaUrl, $step->file_name);

        if ($type === WhatsAppFlowStepType::Image && $extension === 'svg') {
            return [
                'success' => false,
                'http_status' => 0,
                'response' => null,
                'error' => 'WhatsApp não suporta imagens SVG. Use JPG, PNG, GIF ou WEBP.',
            ];
        }

        $mediatype = match ($type) {
            WhatsAppFlowStepType::Image => 'image',
            WhatsAppFlowStepType::Video => 'video',
            WhatsAppFlowStepType::File => 'document',
            default => 'document',
        };

        $mimetype = $this->resolveMimeType($type, $extension);
        $fileName = $step->file_name ?: $this->resolveFileName($mediaUrl, $extension, $mediatype);

        $response = Http::timeout(30)
            ->withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post("{$this->baseUrl}/message/sendMedia/{$this->instance}", [
                'number' => $phone,
                'mediatype' => $mediatype,
                'mimetype' => $mimetype,
                'media' => $mediaUrl,
                'caption' => $step->caption ?? '',
                'fileName' => $fileName,
            ]);

        return $this->parseResponse($response);
    }

    private function resolveMediaExtension(string $mediaUrl, ?string $fileName): string
    {
        $path = $fileName ?: (parse_url($mediaUrl, PHP_URL_PATH) ?? '');

        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    private function resolveMimeType(WhatsAppFlowStepType $type, string $extension): string
    {
        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'pdf' => 'application/pdf',
            'mp3' => 'audio/mpeg',
            'ogg' => 'audio/ogg',
            default => match ($type) {
                WhatsAppFlowStepType::Image => 'image/jpeg',
                WhatsAppFlowStepType::Video => 'video/mp4',
                WhatsAppFlowStepType::File => 'application/pdf',
                default => 'application/octet-stream',
            },
        };
    }

    private function resolveFileName(string $mediaUrl, string $extension, string $mediatype): string
    {
        $basename = basename(parse_url($mediaUrl, PHP_URL_PATH) ?? '');

        if (filled($basename) && str_contains($basename, '.')) {
            return $basename;
        }

        $fallbackExtension = $extension ?: match ($mediatype) {
            'image' => 'jpg',
            'video' => 'mp4',
            default => 'bin',
        };

        return "media.{$fallbackExtension}";
    }

    /**
     * @return array{success: bool, http_status: int, response: array|null, error: string|null}
     */
    private function sendAudio(WhatsAppFlowStep $step, string $phone): array
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post("{$this->baseUrl}/message/sendAudio/{$this->instance}", [
                'number' => $phone,
                'audio' => $step->media_url ?? $step->content,
                'encoding' => true,
            ]);

        return $this->parseResponse($response);
    }

    /**
     * @return array{success: bool, http_status: int, response: array|null, error: string|null}
     */
    private function parseResponse(Response $response): array
    {
        $status = $response->status();
        $body = $response->json();

        if ($response->successful()) {
            return [
                'success' => true,
                'http_status' => $status,
                'response' => is_array($body) ? $body : [],
                'error' => null,
            ];
        }

        $error = is_array($body)
            ? ($body['message'] ?? json_encode($body, JSON_UNESCAPED_UNICODE))
            : $response->body();

        return [
            'success' => false,
            'http_status' => $status,
            'response' => is_array($body) ? $body : null,
            'error' => (string) $error,
        ];
    }

    private function plainTextContent(?string $content): string
    {
        if (blank($content)) {
            return '';
        }

        $normalized = str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", $content);
        $plain = html_entity_decode(strip_tags($normalized), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace("/\n{3,}/", "\n\n", $plain) ?? $plain);
    }
}
