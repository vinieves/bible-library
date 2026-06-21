<?php

namespace App\Filament\Support;

use App\Enums\WhatsAppFlowStepType;
use App\Support\WhatsAppFlowStepMedia;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class FlowStepPreview
{
    public static function previewText(array $state): string
    {
        $type = WhatsAppFlowStepType::tryFrom($state['type'] ?? '');

        if ($type === WhatsAppFlowStepType::Text && filled($state['content'] ?? null)) {
            return Str::limit(strip_tags((string) $state['content']), 40);
        }

        if ($type === WhatsAppFlowStepType::WaitForResponse) {
            return 'Pausa até o contato responder';
        }

        if ($type === WhatsAppFlowStepType::Delay) {
            $seconds = (int) ($state['delay_seconds'] ?? 0);

            return $seconds > 0 ? "Aguarda {$seconds}s" : 'Intervalo';
        }

        if (filled($state['media_path'] ?? null) || filled($state['media_url'] ?? null)) {
            return Str::limit(
                WhatsAppFlowStepMedia::displayName(
                    $state['media_path'] ?? null,
                    $state['media_url'] ?? null,
                    $state['file_name'] ?? null,
                ),
                40
            );
        }

        return 'Clique para editar';
    }

    /**
     * @return list<string>
     */
    public static function metaChips(array $state): array
    {
        $type = WhatsAppFlowStepType::tryFrom($state['type'] ?? '');
        $chips = [];

        if ($type !== WhatsAppFlowStepType::Delay && $type !== WhatsAppFlowStepType::WaitForResponse) {
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

            $variationCount = count(array_filter([
                $state['content'] ?? null,
                $state['content_variation_2'] ?? null,
                $state['content_variation_3'] ?? null,
            ], fn (mixed $value): bool => filled($value)));

            if ($variationCount > 1) {
                $chips[] = "{$variationCount} textos";
            }
        }

        if ($type === WhatsAppFlowStepType::Audio) {
            $recording = (int) ($state['recording_delay'] ?? 0);

            if ($recording > 0) {
                $chips[] = "Gravando {$recording}s";
            }
        }

        if ($type === WhatsAppFlowStepType::WaitForResponse) {
            $chips[] = 'Bloqueio';

            $delay = (int) ($state['delay_seconds'] ?? 0);

            if ($delay > 0) {
                $chips[] = "Espera {$delay}s";
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
        $icon = self::typeIconSvg($type);
        $chips = self::metaChips($state);

        $chipsHtml = $chips === []
            ? ''
            : '<div class="flow-step-card__chips">'.collect($chips)
                ->map(fn (string $chip): string => '<span class="flow-step-card__chip">'
                    .self::chipIconSvg()
                    .e($chip)
                    .'</span>')
                ->implode('')
                .'</div>';

        return new HtmlString(
            '<div class="flow-step-card" style="--step-color: '.$accent.'">'.
            '<span class="flow-step-card__accent" aria-hidden="true"></span>'.
            '<div class="flow-step-card__inner">'.
            '<div class="flow-step-card__type-row">'.
            '<span class="flow-step-card__type">'.$icon.$label.'</span>'.
            '</div>'.
            '<p class="flow-step-card__preview">'.$preview.'</p>'.
            $chipsHtml.
            '</div>'.
            '</div>'
        );
    }

    private static function typeIconSvg(?WhatsAppFlowStepType $type): string
    {
        $path = match ($type) {
            WhatsAppFlowStepType::Image => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z',
            WhatsAppFlowStepType::Video => 'm15.75 10.5 4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z',
            WhatsAppFlowStepType::Audio => 'M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z',
            WhatsAppFlowStepType::File => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
            WhatsAppFlowStepType::Delay => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
            WhatsAppFlowStepType::WaitForResponse => 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z',
            default => 'M19.5 14.25h-9a2.25 2.25 0 010-4.5h9m0 0V8.25m0 6.75V18m-3.75-6.75h-9a2.25 2.25 0 000 4.5h9',
        };

        return '<svg class="flow-step-card__type-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="'.$path.'"/></svg>';
    }

    private static function chipIconSvg(): string
    {
        return '<svg class="flow-step-card__chip-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/></svg>';
    }
}
