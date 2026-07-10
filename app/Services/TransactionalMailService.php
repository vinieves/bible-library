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
     * @return array{sent: bool, response: array<string, mixed>}
     *
     * @throws TransportExceptionInterface
     */
    public function send(string $to, string $subject, string $body): array
    {
        $this->applyRuntimeMailConfig();

        Mail::mailer('transactional')->to($to)->send(
            new HotmartTransactionalMail($subject, $body)
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

        $scheme = $encryption === 'ssl' ? 'smtps' : 'smtp';
        $url = sprintf(
            '%s://%s:%s@%s:%d',
            $scheme,
            rawurlencode((string) $username),
            rawurlencode((string) $password),
            $host,
            $port,
        );

        if ($encryption === 'tls') {
            $url .= '?encryption=tls';
        }

        Config::set('mail.mailers.transactional', [
            'transport' => 'smtp',
            'url' => $url,
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
    }
}
