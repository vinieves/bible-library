<?php

namespace App\Services;

use App\Enums\WhatsAppMessageEvent;
use App\Models\EmailMessageTemplate;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Str;

class EmailMessageTemplateService
{
    public function subject(WhatsAppMessageEvent $event): string
    {
        $template = $this->find($event);

        if ($template && filled($template->subject)) {
            return $template->subject;
        }

        return $this->defaultSubject($event);
    }

    public function body(WhatsAppMessageEvent $event): string
    {
        $template = $this->find($event);

        if ($template && filled($template->body)) {
            return $template->body;
        }

        return $event->defaultBody();
    }

    public function isEnabled(WhatsAppMessageEvent $event): bool
    {
        $template = $this->find($event);

        if (! $template) {
            return false;
        }

        return $template->is_enabled;
    }

    public function renderSubject(
        WhatsAppMessageEvent $event,
        User $user,
        ?Purchase $purchase = null,
        ?NormalizedPurchaseContext $context = null,
    ): string {
        return $this->replacePlaceholders($this->subject($event), $event, $user, $purchase, $context);
    }

    public function renderBody(
        WhatsAppMessageEvent $event,
        User $user,
        ?Purchase $purchase = null,
        ?NormalizedPurchaseContext $context = null,
    ): string {
        return $this->replacePlaceholders($this->body($event), $event, $user, $purchase, $context);
    }

    public function renderBodyHtml(
        WhatsAppMessageEvent $event,
        User $user,
        ?Purchase $purchase = null,
        ?NormalizedPurchaseContext $context = null,
    ): string {
        $template = $this->find($event);
        $markedBody = $this->replacePlaceholders(
            $this->body($event),
            $event,
            $user,
            $purchase,
            $context,
            forEmailHtml: true,
        );

        return app(EmailBodyRenderer::class)->renderFromMarkedBody(
            $markedBody,
            $template?->inline_images ?? [],
        );
    }

    /**
     * @return list<string>
     */
    public function attachments(WhatsAppMessageEvent $event): array
    {
        $template = $this->find($event);

        return array_values(array_filter($template?->attachments ?? []));
    }

    /**
     * @return array<string, string>
     */
    public function inlineImages(WhatsAppMessageEvent $event): array
    {
        $template = $this->find($event);

        return $template?->inline_images ?? [];
    }

    public function preview(string $text): string
    {
        return $this->replacePlaceholders($text, preview: true);
    }

    /**
     * @param  array<string, string>  $inlineImages
     */
    public function previewHtml(string $text, array $inlineImages = []): string
    {
        $markedBody = $this->replacePlaceholders($text, preview: true, forEmailHtml: true);

        return app(EmailBodyRenderer::class)->renderFromMarkedBody($markedBody, $inlineImages);
    }

    /**
     * @param  list<string>  $uploadedPaths
     * @return array<string, string>
     */
    public function inlineImagesFromPaths(array $uploadedPaths): array
    {
        $map = [];

        foreach (array_values(array_filter($uploadedPaths)) as $path) {
            $filename = pathinfo((string) $path, PATHINFO_FILENAME);
            $slug = Str::slug($filename);

            if (filled($slug)) {
                $map[$slug] = (string) $path;
            }
        }

        return $map;
    }

    /**
     * @param  list<string>|mixed  $uploadedPaths
     * @return list<string>
     */
    public function normalizeAttachmentPaths(mixed $uploadedPaths): array
    {
        if (! is_array($uploadedPaths)) {
            return [];
        }

        return array_values(array_filter($uploadedPaths));
    }

    public function upsert(
        WhatsAppMessageEvent $event,
        string $subject,
        string $body,
        bool $isEnabled,
        array $inlineImages = [],
        array $attachments = [],
    ): EmailMessageTemplate {
        return EmailMessageTemplate::query()->updateOrCreate(
            ['event' => $event->value],
            [
                'subject' => $subject,
                'body' => $body,
                'inline_images' => $inlineImages,
                'attachments' => $attachments,
                'is_enabled' => $isEnabled,
                'sort_order' => $this->sortOrder($event),
            ]
        );
    }

    public function toggleEnabled(WhatsAppMessageEvent $event): EmailMessageTemplate
    {
        $template = $this->find($event);

        if (! $template) {
            throw new \RuntimeException("Regra não encontrada para {$event->value}.");
        }

        return $this->upsert(
            $event,
            $template->subject,
            $template->body,
            ! $template->is_enabled,
            $template->inline_images ?? [],
            $template->attachments ?? [],
        );
    }

    /**
     * @return \Illuminate\Support\Collection<int, EmailMessageTemplate>
     */
    public function configuredRules(): \Illuminate\Support\Collection
    {
        return EmailMessageTemplate::query()
            ->get()
            ->sortBy(fn (EmailMessageTemplate $template): array => [
                $template->event->groupSortOrder(),
                $template->sort_order,
                $template->id,
            ])
            ->values();
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function availableConditionOptions(): array
    {
        $existing = EmailMessageTemplate::query()
            ->pluck('event')
            ->map(fn (WhatsAppMessageEvent|string $event): string => $event instanceof WhatsAppMessageEvent ? $event->value : $event)
            ->all();

        return collect(WhatsAppMessageEvent::creatableCases())
            ->reject(fn (WhatsAppMessageEvent $event): bool => in_array($event->value, $existing, true))
            ->groupBy(fn (WhatsAppMessageEvent $event): string => $event->group())
            ->sortBy(fn ($events, string $group): int => match ($group) {
                'Vendas aprovadas' => 1,
                'Recuperação de vendas' => 2,
                'Pós-venda e pagamentos' => 3,
                'Sistema' => 4,
                default => 99,
            })
            ->map(fn ($events): array => $events
                ->mapWithKeys(fn (WhatsAppMessageEvent $event): array => [$event->value => $event->conditionLabel()])
                ->all())
            ->all();
    }

    public function deleteRule(WhatsAppMessageEvent $event): void
    {
        EmailMessageTemplate::query()
            ->where('event', $event->value)
            ->delete();
    }

    private function find(WhatsAppMessageEvent $event): ?EmailMessageTemplate
    {
        return EmailMessageTemplate::query()
            ->where('event', $event->value)
            ->first();
    }

    private function sortOrder(WhatsAppMessageEvent $event): int
    {
        return array_search($event, WhatsAppMessageEvent::cases(), true) + 1;
    }

    private function defaultSubject(WhatsAppMessageEvent $event): string
    {
        return match ($event) {
            WhatsAppMessageEvent::PurchaseApproved => 'Su acceso a la Biblioteca Bíblica Digital',
            WhatsAppMessageEvent::PurchaseComplete => 'Compra finalizada — {producto}',
            WhatsAppMessageEvent::PurchaseFunnel => 'Confirmación de su compra — {producto}',
            WhatsAppMessageEvent::PurchaseCanceled => 'Compra cancelada — {producto}',
            WhatsAppMessageEvent::PurchaseBilletPrinted => 'Boleto generado — {producto}',
            WhatsAppMessageEvent::PurchaseProtest => 'Solicitud de reembolso recibida',
            WhatsAppMessageEvent::PurchaseRefunded => 'Reembolso confirmado — {producto}',
            WhatsAppMessageEvent::PurchaseChargeback => 'Chargeback registrado — {transacao}',
            WhatsAppMessageEvent::PurchaseExpired => 'Oportunidad de compra expirada',
            WhatsAppMessageEvent::PurchaseDelayed => 'Pago pendiente — {producto}',
            WhatsAppMessageEvent::PurchaseOutOfShoppingCart => '¿Aún le interesa {producto}?',
            WhatsAppMessageEvent::ManualTest => 'Mensaje de prueba — Biblioteca Bíblica Digital',
        };
    }

    private function replacePlaceholders(
        string $text,
        ?WhatsAppMessageEvent $event = null,
        ?User $user = null,
        ?Purchase $purchase = null,
        ?NormalizedPurchaseContext $context = null,
        bool $preview = false,
        bool $forEmailHtml = false,
    ): string {
        if ($preview) {
            $accessUrl = route('login');
            $checkoutUrl = (string) Setting::get('checkout_completo_url', route('home'));

            $replacements = [
                '{nome}' => 'María García',
                '{email}' => 'maria@ejemplo.com',
                '{telefone}' => '5215512345678',
                '{producto}' => 'Plan Completo — Hotmart',
                '{link_acceso}' => $forEmailHtml
                    ? EmailBodyRenderer::accessMarker($accessUrl)
                    : $accessUrl,
                '{link_checkout}' => $forEmailHtml
                    ? EmailBodyRenderer::checkoutMarker($checkoutUrl)
                    : $checkoutUrl,
                '{transacao}' => 'HP1234567890',
                '{evento}' => 'PURCHASE_APPROVED',
                '{moeda}' => 'USD',
                '{valor}' => '4.90',
            ];

            return str_replace(
                array_keys($replacements),
                array_values($replacements),
                $text
            );
        }

        $event ??= WhatsAppMessageEvent::PurchaseApproved;
        $user ??= new User(['name' => 'Cliente', 'email' => 'cliente@ejemplo.com']);

        $productTitle = $purchase?->product?->title
            ?? $context?->productTitle
            ?? '';

        $accessUrl = route('login');
        $checkoutUrl = $this->resolveCheckoutLink($event, $context?->productTitle);

        $replacements = [
            '{nome}' => $user->name,
            '{email}' => $user->email,
            '{telefone}' => $purchase?->phone ?? $context?->phone ?? '',
            '{producto}' => $productTitle,
            '{link_acceso}' => $forEmailHtml
                ? EmailBodyRenderer::accessMarker($accessUrl)
                : $accessUrl,
            '{link_checkout}' => filled($checkoutUrl)
                ? ($forEmailHtml ? EmailBodyRenderer::checkoutMarker($checkoutUrl) : $checkoutUrl)
                : '',
            '{transacao}' => $purchase?->external_reference ?? $context?->transaction ?? '',
            '{evento}' => $context?->hotmartEvent ?? $event->hotmartEvent(),
            '{moeda}' => $context?->currency ?? '',
            '{valor}' => $this->formatAmount($purchase?->amount ?? $context?->amount),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $text
        );
    }

    private function formatAmount(mixed $amount): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        return number_format((float) $amount, 2, '.', '');
    }

    private function resolveCheckoutLink(WhatsAppMessageEvent $event, ?string $productTitle): string
    {
        if ($event !== WhatsAppMessageEvent::PurchaseOutOfShoppingCart) {
            return '';
        }

        if (filled($productTitle)) {
            $product = Product::query()
                ->where('title', $productTitle)
                ->whereNotNull('checkout_url')
                ->where('checkout_url', '!=', '')
                ->first();

            if ($product?->checkout_url) {
                return $product->checkout_url;
            }
        }

        $checkoutUrl = Setting::get('checkout_completo_url', '');

        return filled($checkoutUrl) ? $checkoutUrl : route('home');
    }
}
