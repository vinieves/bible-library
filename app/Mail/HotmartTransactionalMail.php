<?php

namespace App\Mail;

use App\Support\IntegrationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

class HotmartTransactionalMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{full_path: string, name: string, mime: string, disk?: string, path?: string}>  $resolvedAttachments
     * @param  list<array{cid: string, full_path: string, name?: string, mime?: string}>  $inlineEmbeds
     */
    public function __construct(
        public string $subjectLine,
        public string $bodyHtml,
        public array $resolvedAttachments = [],
        public array $inlineEmbeds = [],
    ) {
        if ($this->inlineEmbeds !== []) {
            $this->withSymfonyMessage(function (Email $message): void {
                foreach ($this->inlineEmbeds as $embed) {
                    $message->embedFromPath(
                        $embed['full_path'],
                        $embed['cid'],
                        $embed['mime'] ?? null,
                    );
                }
            });
        }
    }

    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            subject: $this->subjectLine,
        );

        $replyTo = IntegrationSettings::emailReplyTo();

        if (filled($replyTo)) {
            $envelope = $envelope->replyTo([new Address($replyTo)]);
        }

        return $envelope;
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->bodyHtml,
        );
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return collect($this->resolvedAttachments)
            ->map(function (array $attachment): Attachment {
                if (filled($attachment['disk'] ?? null) && filled($attachment['path'] ?? null)) {
                    return Attachment::fromStorageDisk((string) $attachment['disk'], (string) $attachment['path'])
                        ->as($attachment['name'])
                        ->withMime($attachment['mime']);
                }

                return Attachment::fromPath($attachment['full_path'])
                    ->as($attachment['name'])
                    ->withMime($attachment['mime']);
            })
            ->all();
    }
}
