<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DigestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $digestTitle,
        public array $sections,
        public string $recipientName = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->digestTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.digest',
        );
    }
}
