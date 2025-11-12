<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChangeEmailMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $newEmail;
    public $token;
    public $confirmUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $newEmail, $token)
    {
        $this->user = $user;
        $this->newEmail = $newEmail;
        $this->token = $token;
        $this->confirmUrl = config('app.frontend_url') . "/auth/confirm-email-change?token={$token}";
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm Your New Email Address - CharityConnect',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.change-email',
            with: [
                'userName' => $this->user->name,
                'oldEmail' => $this->user->email,
                'newEmail' => $this->newEmail,
                'confirmUrl' => $this->confirmUrl,
                'expiresIn' => 60, // minutes
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
