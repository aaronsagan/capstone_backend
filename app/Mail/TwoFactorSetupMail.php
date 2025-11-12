<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorSetupMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $backupCodes;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $backupCodes)
    {
        $this->user = $user;
        $this->backupCodes = $backupCodes;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '2FA Setup Confirmation & Backup Codes - CharityConnect',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.2fa-setup',
            with: [
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'backupCodes' => $this->backupCodes,
                'setupDate' => now()->format('F d, Y h:i A'),
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
