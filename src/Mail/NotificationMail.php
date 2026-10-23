<?php

namespace Whilesmart\Notifications\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Whilesmart\Notifications\Support\NotificationMessage;

class NotificationMail extends Mailable
{
    use Queueable;

    public function __construct(
        public NotificationMessage $message,
        public ?string $unsubscribeUrl = null,
        public ?string $recipientName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->message->title);
    }

    public function headers(): Headers
    {
        return new Headers(text: $this->unsubscribeUrl ? [
            'List-Unsubscribe' => '<'.$this->unsubscribeUrl.'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ] : []);
    }

    public function content(): Content
    {
        return new Content(markdown: 'notifications::mail', with: [
            'notification' => $this->message,
            'recipientName' => $this->recipientName,
            'unsubscribeUrl' => $this->unsubscribeUrl,
        ]);
    }
}
