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
        // Tons espelham resources/css/tokens.css — paleta beige/marrom.
        return match ($this) {
            self::Text => '#6E4C2C',
            self::Image => '#C39A5E',
            self::Video => '#D8B583',
            self::Audio => '#CDA871',
            self::File => '#3A2616',
            self::Delay => '#2F2117',
            self::WaitForResponse => '#8C7A63',
        };
    }
}
