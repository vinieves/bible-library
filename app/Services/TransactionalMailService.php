<?php

namespace App\Services;

use App\Mail\HotmartTransactionalMail;
use App\Support\IntegrationSettings;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class TransactionalMailService
{
    public function isConfigured(): bool
    {
        return IntegrationSettings::emailSmtpConfigured();
    }

    /**
     * @param  list<array{path: string, name?: string}|string>  $attachmentRecords
     * @param  list<array{cid: string, full_path: string, name?: string, mime?: string}>  $inlineEmbeds
     * @return array{sent: bool, response: array<string, mixed>}
     *
     * @throws TransportExceptionInterface
     */
    public function send(
        string $to,
        string $subject,
        string $bodyHtml,
        array $attachmentRecords = [],
        array $inlineEmbeds = [],
    ): array {
        $this->applyRuntimeMailConfig();

        $resolvedAttachments = app(EmailAttachmentResolver::class)->resolveMany($attachmentRecords);

        if ($attachmentRecords !== [] && $resolvedAttachments === []) {
            Log::warning('E-mail enviado sem anexos: nenhum arquivo foi encontrado no disco.', [
                'to' => $to,
                'subject' => $subject,
                'requested' => $attachmentRecords,
            ]);
        }

        if ($inlineEmbeds === [] && str_contains($bodyHtml, 'cid:email-img-')) {
            Log::warning('E-mail com imagens CID no HTML, mas nenhum embed foi resolvido.', [
                'to' => $to,
                'subject' => $subject,
            ]);
        }

        Mail::mailer('transactional')->to($to)->send(
            new HotmartTransactionalMail($subject, $bodyHtml, $resolvedAttachments, $inlineEmbeds)
        );

        return [
            'sent' => true,
            'response' => [
                'to' => $to,
                'from' => IntegrationSettings::mailFromAddress(),
                'subject' => $subject,
                'attachments' => collect($resolvedAttachments)
                    ->pluck('name')
                    ->values()
                    ->all(),
                'inline_images' => collect($inlineEmbeds)
                    ->pluck('cid')
                    ->values()
                    ->all(),
            ],
        ];
    }

    private function applyRuntimeMailConfig(): void
    {
        $host = IntegrationSettings::smtpHost();
        $port = IntegrationSettings::smtpPort();
        $encryption = IntegrationSettings::smtpEncryption();
        $username = IntegrationSettings::smtpUsername();
        $password = IntegrationSettings::smtpPassword();

        // scheme = smtps (SSL/465) ou smtp (TLS/587). Não usar URL smtps:// — o Laravel
        // interpreta o scheme da URL como nome de transporte e lança "Unsupported mail transport [smtps]".
        $scheme = $encryption === 'ssl' ? 'smtps' : 'smtp';

        Config::set('mail.mailers.transactional', [
            'transport' => 'smtp',
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $password,
            'timeout' => 30,
            'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
        ]);

        Config::set('mail.from', [
            'address' => IntegrationSettings::mailFromAddress(),
            'name' => IntegrationSettings::mailFromName(),
        ]);

        Config::set('mail.default', 'transactional');

        Mail::purge('transactional');
    }
}
