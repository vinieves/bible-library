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
use Illuminate\Support\Facades\Storage;

class HotmartTransactionalMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{path: string, name?: string}>|list<string>  $attachmentPaths
     */
    public function __construct(
        public string $subjectLine,
        public string $bodyHtml,
        public array $attachmentPaths = [],
    ) {}

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
        $attachments = [];

        foreach ($this->attachmentPaths as $attachment) {
            $path = is_array($attachment) ? ($attachment['path'] ?? null) : $attachment;
            $name = is_array($attachment) ? ($attachment['name'] ?? basename((string) $path)) : basename((string) $attachment);

            if (blank($path) || ! Storage::disk('public')->exists($path)) {
                continue;
            }

            $attachments[] = Attachment::fromStorageDisk('public', $path)
                ->as($name);
        }

        return $attachments;
    }
}
