<?php

namespace App\Mail;

use App\Support\IntegrationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HotmartTransactionalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $bodyHtml,
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
}
