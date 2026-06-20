<?php

namespace App\Filament\Support;

use App\Enums\WhatsAppFlowStepType;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class FlowStepPreview
{
    public static function previewText(array $state): string
    {
        $type = WhatsAppFlowStepType::tryFrom($state['type'] ?? '');

        if ($type === WhatsAppFlowStepType::Text && filled($state['content'] ?? null)) {
            return Str::limit(strip_tags((string) $state['content']), 48);
        }

        if ($type === WhatsAppFlowStepType::Delay) {
            $seconds = (int) ($state['delay_seconds'] ?? 0);

            return $seconds > 0 ? "Aguarda {$seconds}s" : 'Sem espera';
        }

        if (filled($state['media_url'] ?? null)) {
            return Str::limit((string) $state['media_url'], 48);
        }

        return 'Configure este passo';
    }

    public static function metaText(array $state): ?string
    {
        $type = WhatsAppFlowStepType::tryFrom($state['type'] ?? '');
        $parts = [];

        if ($type !== WhatsAppFlowStepType::Delay) {
            $delay = (int) ($state['delay_seconds'] ?? 0);

            if ($delay > 0) {
                $parts[] = "Espera {$delay}s";
            }
        }

        if ($type === WhatsAppFlowStepType::Text) {
            $typing = (int) ($state['typing_delay'] ?? 0);

            if ($typing > 0) {
                $parts[] = "Digitando {$typing}s";
            }
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }

    public static function itemLabel(array $state): HtmlString
    {
        $type = WhatsAppFlowStepType::tryFrom($state['type'] ?? '');
        $label = e($type?->label() ?? 'Passo');
        $preview = e(self::previewText($state));
        $meta = self::metaText($state);
        $accent = e($type?->color() ?? '#71717a');

        $metaHtml = filled($meta)
            ? '<span class="flow-step-card__meta">'.e($meta).'</span>'
            : '';

        return new HtmlString(
            '<div class="flow-step-card__label" style="--flow-step-accent: '.$accent.'">'.
            '<span class="flow-step-card__accent" aria-hidden="true"></span>'.
            '<span class="flow-step-card__body">'.
            '<span class="flow-step-card__type">'.$label.'</span>'.
            '<span class="flow-step-card__preview">'.$preview.'</span>'.
            $metaHtml.
            '</span>'.
            '</div>'
        );
    }
}
