<?php

namespace App\Enums;

enum WhatsAppFlowStepType: string
{
    case Text = 'text';
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case File = 'file';
    case Delay = 'delay';
    case WaitForResponse = 'wait_for_response';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Texto',
            self::Image => 'Imagem',
            self::Video => 'Vídeo',
            self::Audio => 'Áudio',
            self::File => 'Arquivo',
            self::Delay => 'Intervalo',
            self::WaitForResponse => 'Aguardar resposta',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Text => 'heroicon-o-chat-bubble-left',
            self::Image => 'heroicon-o-photo',
            self::Video => 'heroicon-o-video-camera',
            self::Audio => 'heroicon-o-microphone',
            self::File => 'heroicon-o-document',
            self::Delay => 'heroicon-o-clock',
            self::WaitForResponse => 'heroicon-o-chat-bubble-left-right',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Text => '#3b82f6',
            self::Image => '#22c55e',
            self::Video => '#a855f7',
            self::Audio => '#f97316',
            self::File => '#6366f1',
            self::Delay => '#14b8a6',
            self::WaitForResponse => '#eab308',
        };
    }
}
