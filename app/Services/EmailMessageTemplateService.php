<?php

namespace App\Services;

use App\Enums\WhatsAppMessageEvent;
use App\Models\EmailMessageTemplate;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\User;

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
            $this->inlineImagesMap($template?->inline_images),
        );
    }

    /**
     * @return list<string>
     */
    public function attachments(WhatsAppMessageEvent $event): array
    {
        $template = $this->find($event);

        return $this->normalizeAttachmentRecords($template?->attachments);
    }

    /**
     * @return array<string, string>
     */
    public function inlineImages(WhatsAppMessageEvent $event): array
    {
        $template = $this->find($event);

        return $this->inlineImagesMap($template?->inline_images);
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
     * @param  mixed  $uploadedPaths
     * @param  mixed  $existingStored
     * @return list<array{slug: string, path: string, name: string}>
     */
    public function inlineImagesFromUpload(mixed $uploadedPaths, mixed $existingStored = null): array
    {
        $paths = $this->flattenUploadPaths($uploadedPaths);
        $existing = collect($this->normalizeInlineImageRecords($existingStored))->keyBy('path');

        $records = [];

        foreach ($paths as $path) {
            if ($existing->has($path)) {
                $records[] = $existing->get($path);

                continue;
            }

            $slug = (string) pathinfo($path, PATHINFO_FILENAME);

            if (blank($slug)) {
                continue;
            }

            $records[] = [
                'slug' => $slug,
                'path' => $path,
                'name' => basename($path),
            ];
        }

        return $records;
    }

    /**
     * @param  mixed  $stored
     * @return list<string>
     */
    public function inlineImagePathsForForm(mixed $stored): array
    {
        return collect($this->normalizeInlineImageRecords($stored))
            ->pluck('path')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  mixed  $stored
     * @return array<string, string>
     */
    public function inlineImagesMap(mixed $stored): array
    {
        return collect($this->normalizeInlineImageRecords($stored))
            ->mapWithKeys(fn (array $record): array => [$record['slug'] => $record['path']])
            ->all();
    }

    /**
     * @param  mixed  $uploadedPaths
     * @param  mixed  $existingStored
     * @return list<array{path: string, name: string}>
     */
    public function attachmentsFromUpload(mixed $uploadedPaths, mixed $existingStored = null): array
    {
        $paths = $this->flattenUploadPaths($uploadedPaths);
        $resolver = app(EmailAttachmentResolver::class);

        $existing = collect($this->normalizeAttachmentRecords($existingStored))
            ->mapWithKeys(fn (array $record): array => [
                $resolver->normalizeRelativePath($record['path']) => [
                    'path' => $resolver->normalizeRelativePath($record['path']),
                    'name' => $record['name'],
                ],
            ]);

        $records = [];

        foreach ($paths as $path) {
            $normalizedPath = $resolver->normalizeRelativePath($path);

            if (blank($normalizedPath)) {
                continue;
            }

            $records[] = $existing->get($normalizedPath) ?? [
                'path' => $normalizedPath,
                'name' => basename($normalizedPath),
            ];
        }

        return $records;
    }

    /**
     * @param  mixed  $stored
     * @return list<string>
     */
    public function attachmentPathsForForm(mixed $stored): array
    {
        return $this->attachmentPaths($stored);
    }

    /**
     * @param  mixed  $stored
     * @return list<string>
     */
    public function attachmentPaths(mixed $stored): array
    {
        return collect($this->normalizeAttachmentRecords($stored))
            ->pluck('path')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  mixed  $uploadedPaths
     * @return array<string, string>
     */
    public function inlineImagesMapFromUpload(mixed $uploadedPaths, mixed $existingStored = null): array
    {
        return $this->inlineImagesMap(
            $this->inlineImagesFromUpload($uploadedPaths, $existingStored),
        );
    }

    /**
     * @param  mixed  $stored
     * @return list<array{slug: string, path: string, name: string}>
     */
    public function normalizeInlineImageRecords(mixed $stored): array
    {
        if (! is_array($stored) || $stored === []) {
            return [];
        }

        if (isset($stored[0]) && is_array($stored[0]) && isset($stored[0]['path'])) {
            return collect($stored)
                ->filter(fn (array $record): bool => filled($record['path'] ?? null))
                ->map(fn (array $record): array => [
                    'slug' => (string) ($record['slug'] ?? pathinfo((string) $record['path'], PATHINFO_FILENAME)),
                    'path' => (string) $record['path'],
                    'name' => (string) ($record['name'] ?? basename((string) $record['path'])),
                ])
                ->values()
                ->all();
        }

        $records = [];

        foreach ($stored as $key => $value) {
            if (! is_string($value) || blank($value)) {
                continue;
            }

            $records[] = [
                'slug' => is_string($key) ? $key : (string) pathinfo($value, PATHINFO_FILENAME),
                'path' => $value,
                'name' => basename($value),
            ];
        }

        return $records;
    }

    /**
     * @param  mixed  $stored
     * @return list<array{path: string, name: string}>
     */
    public function normalizeAttachmentRecords(mixed $stored): array
    {
        if (! is_array($stored) || $stored === []) {
            return [];
        }

        $resolver = app(EmailAttachmentResolver::class);

        if (isset($stored[0]) && is_array($stored[0]) && isset($stored[0]['path'])) {
            return collect($stored)
                ->filter(fn (array $record): bool => filled($record['path'] ?? null))
                ->map(fn (array $record): array => [
                    'path' => $resolver->normalizeRelativePath((string) $record['path']),
                    'name' => (string) ($record['name'] ?? basename((string) $record['path'])),
                ])
                ->values()
                ->all();
        }

        return collect($this->flattenUploadPaths($stored))
            ->map(fn (string $path): array => [
                'path' => $resolver->normalizeRelativePath($path),
                'name' => basename($resolver->normalizeRelativePath($path)),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  mixed  $uploadedPaths
     * @return list<string>
     */
    public function flattenUploadPaths(mixed $uploadedPaths): array
    {
        if (! is_array($uploadedPaths)) {
            return filled($uploadedPaths) ? [(string) $uploadedPaths] : [];
        }

        $paths = [];

        foreach ($uploadedPaths as $value) {
            if (is_array($value)) {
                $paths = [...$paths, ...$this->flattenUploadPaths($value)];

                continue;
            }

            if (filled($value)) {
                $paths[] = (string) $value;
            }
        }

        return array_values(array_unique($paths));
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
            $this->normalizeInlineImageRecords($template->inline_images),
            $this->normalizeAttachmentRecords($template->attachments),
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
