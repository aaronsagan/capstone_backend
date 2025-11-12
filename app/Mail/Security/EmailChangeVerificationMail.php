<?php

namespace App\Mail\Security;

use App\Models\User;
use App\Models\EmailChange;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailChangeVerificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $emailChange;
    public $verificationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, EmailChange $emailChange, string $verificationUrl)
    {
        $this->user = $user;
        $this->emailChange = $emailChange;
        $this->verificationUrl = $verificationUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Verify Your New Email Address - CharityHub",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.security.email-change-verification',
            with: [
                'userName' => $this->user->name,
                'oldEmail' => $this->emailChange->old_email,
                'newEmail' => $this->emailChange->new_email,
                'verificationUrl' => $this->verificationUrl,
                'expiresAt' => $this->emailChange->expires_at->format('F d, Y h:i A'),
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
