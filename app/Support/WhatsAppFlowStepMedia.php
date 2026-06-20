<?php

namespace App\Support;

use App\Enums\WhatsAppFlowStepType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsAppFlowStepMedia
{
    /**
     * @return list<string>
     */
    public static function acceptedMimeTypes(WhatsAppFlowStepType|string|null $type): array
    {
        $type = $type instanceof WhatsAppFlowStepType
            ? $type
            : WhatsAppFlowStepType::tryFrom((string) $type);

        return match ($type) {
            WhatsAppFlowStepType::Image => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
            ],
            WhatsAppFlowStepType::Video => [
                'video/mp4',
                'video/webm',
            ],
            WhatsAppFlowStepType::Audio => [
                'audio/mpeg',
                'audio/mp3',
                'audio/ogg',
                'audio/opus',
                'audio/webm',
            ],
            WhatsAppFlowStepType::File => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip',
                'text/plain',
            ],
            default => [],
        };
    }

    public static function maxUploadSizeKb(WhatsAppFlowStepType|string|null $type): int
    {
        $type = $type instanceof WhatsAppFlowStepType
            ? $type
            : WhatsAppFlowStepType::tryFrom((string) $type);

        return match ($type) {
            WhatsAppFlowStepType::Image => 5120,
            WhatsAppFlowStepType::Video => 16384,
            WhatsAppFlowStepType::Audio => 16384,
            WhatsAppFlowStepType::File => 20480,
            default => 5120,
        };
    }

    public static function uploadDirectory(?int $flowId): string
    {
        $folder = $flowId ? (string) $flowId : 'draft';

        return "whatsapp-flow-media/{$folder}";
    }

    public static function publicUrl(?string $mediaPath, ?string $mediaUrl = null): ?string
    {
        if (filled($mediaPath) && Storage::disk('public')->exists($mediaPath)) {
            return Storage::disk('public')->url($mediaPath);
        }

        if (filled($mediaUrl)) {
            return trim($mediaUrl);
        }

        return null;
    }

    public static function displayName(?string $mediaPath, ?string $mediaUrl = null, ?string $fileName = null): string
    {
        if (filled($fileName)) {
            return $fileName;
        }

        if (filled($mediaPath)) {
            return basename($mediaPath);
        }

        if (filled($mediaUrl)) {
            $path = parse_url($mediaUrl, PHP_URL_PATH);

            return filled($path) ? basename($path) : Str::limit($mediaUrl, 40);
        }

        return 'Clique para editar';
    }
}
