<?php

namespace App\Services;

use App\Mail\HotmartTransactionalMail;
use App\Support\IntegrationSettings;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class TransactionalMailService
{
    public function isConfigured(): bool
    {
        return IntegrationSettings::emailSmtpConfigured();
    }

    /**
     * @param  list<string>  $attachmentPaths
     * @return array{sent: bool, response: array<string, mixed>}
     *
     * @throws TransportExceptionInterface
     */
    public function send(string $to, string $subject, string $bodyHtml, array $attachmentPaths = []): array
    {
        $this->applyRuntimeMailConfig();

        Mail::mailer('transactional')->to($to)->send(
            new HotmartTransactionalMail($subject, $bodyHtml, $attachmentPaths)
        );

        return [
            'sent' => true,
            'response' => [
                'to' => $to,
                'from' => IntegrationSettings::mailFromAddress(),
                'subject' => $subject,
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
