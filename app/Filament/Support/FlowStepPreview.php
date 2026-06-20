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
            return Str::limit(strip_tags((string) $state['content']), 56);
        }

        if ($type === WhatsAppFlowStepType::Delay) {
            $seconds = (int) ($state['delay_seconds'] ?? 0);

            return $seconds > 0 ? "Aguarda {$seconds}s" : 'Intervalo sem espera';
        }

        if (filled($state['media_url'] ?? null)) {
            return Str::limit((string) $state['media_url'], 56);
        }

        return 'Clique para configurar';
    }

    /**
     * @return list<string>
     */
    public static function metaChips(array $state): array
    {
        $type = WhatsAppFlowStepType::tryFrom($state['type'] ?? '');
        $chips = [];

        if ($type !== WhatsAppFlowStepType::Delay) {
            $delay = (int) ($state['delay_seconds'] ?? 0);

            if ($delay > 0) {
                $chips[] = "Espera {$delay}s";
            }
        }

        if ($type === WhatsAppFlowStepType::Text) {
            $typing = (int) ($state['typing_delay'] ?? 0);

            if ($typing > 0) {
                $chips[] = "Digitando {$typing}s";
            }
        }

        if ($type === WhatsAppFlowStepType::Delay) {
            $seconds = (int) ($state['delay_seconds'] ?? 0);

            if ($seconds > 0) {
                $chips[] = "{$seconds}s";
            }
        }

        return $chips;
    }

    public static function itemLabel(array $state): HtmlString
    {
        $type = WhatsAppFlowStepType::tryFrom($state['type'] ?? '');
        $label = e($type?->label() ?? 'Passo');
        $preview = e(self::previewText($state));
        $accent = e($type?->color() ?? '#71717a');
        $chips = self::metaChips($state);

        $chipsHtml = $chips === []
            ? ''
            : '<div class="flow-step-card__chips">'.collect($chips)
                ->map(fn (string $chip): string => '<span class="flow-step-card__chip">'.e($chip).'</span>')
                ->implode('')
                .'</div>';

        return new HtmlString(
            '<div class="flow-step-card" style="--step-color: '.$accent.'">'.
            '<span class="flow-step-card__accent" aria-hidden="true"></span>'.
            '<span class="flow-step-card__body">'.
            '<span class="flow-step-card__head">'.
            '<span class="flow-step-card__type">'.$label.'</span>'.
            '</span>'.
            '<p class="flow-step-card__preview">'.$preview.'</p>'.
            $chipsHtml.
            '</span>'.
            '</div>'
        );
    }
}
