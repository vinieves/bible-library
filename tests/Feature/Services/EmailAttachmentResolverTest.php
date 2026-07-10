<?php

namespace Tests\Feature\Services;

use App\Mail\HotmartTransactionalMail;
use App\Services\EmailAttachmentResolver;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmailAttachmentResolverTest extends TestCase
{
    public function test_resolves_relative_path_on_public_disk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('email-rules/attachments/manual.pdf', '%PDF-1.4 test');

        $resolver = app(EmailAttachmentResolver::class);

        $resolved = $resolver->resolve('email-rules/attachments/manual.pdf', 'Manual.pdf');

        $this->assertNotNull($resolved);
        $this->assertSame('public', $resolved['disk']);
        $this->assertSame('email-rules/attachments/manual.pdf', $resolved['path']);
        $this->assertSame('Manual.pdf', $resolved['name']);
        $this->assertFileExists($resolved['full_path']);
    }

    public function test_resolves_storage_url_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('email-rules/attachments/boleto.pdf', '%PDF-1.4 test');

        $resolver = app(EmailAttachmentResolver::class);

        $resolved = $resolver->resolve(
            'https://example.com/storage/email-rules/attachments/boleto.pdf',
            'Boleto.pdf',
        );

        $this->assertNotNull($resolved);
        $this->assertSame('email-rules/attachments/boleto.pdf', $resolved['path']);
    }

    public function test_normalize_relative_path_strips_storage_prefix(): void
    {
        $resolver = app(EmailAttachmentResolver::class);

        $this->assertSame(
            'email-rules/attachments/file.pdf',
            $resolver->normalizeRelativePath('/storage/email-rules/attachments/file.pdf'),
        );
    }

    public function test_transactional_mail_includes_attachments(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('email-rules/attachments/test.pdf', '%PDF-1.4 test');

        config([
            'mail.mailers.transactional' => [
                'transport' => 'array',
            ],
            'mail.from.address' => 'test@example.com',
            'mail.from.name' => 'Test',
        ]);

        Mail::fake();

        $records = [
            [
                'path' => 'email-rules/attachments/test.pdf',
                'name' => 'Teste.pdf',
            ],
        ];

        $resolved = app(EmailAttachmentResolver::class)->resolveMany($records);

        Mail::mailer('transactional')->to('cliente@example.com')->send(
            new HotmartTransactionalMail('Assunto', '<p>Corpo</p>', $resolved),
        );

        Mail::assertSent(HotmartTransactionalMail::class, function (HotmartTransactionalMail $mail): bool {
            $attachments = $mail->attachments();

            return count($attachments) === 1
                && $attachments[0]->as === 'Teste.pdf';
        });
    }
}
