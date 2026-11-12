<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly User $user,
    ) {
        $this->locale = $user->preferredLocale() ?? app()->getLocale();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('app.name').' - '.__('mail.account_created.subject'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        /** @phpstan-var view-string $markdown */
        $markdown = "mail.{$this->locale}.account-created";

        return new Content(
            markdown: $markdown,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
