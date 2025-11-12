<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountDeactivatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🚧 Account Deactivated - CharityHub',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.account-deactivated',
            with: [
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'deactivatedAt' => now()->format('F d, Y h:i A'),
                'reactivationUrl' => $this->getReactivationUrl(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Get reactivation URL based on user role
     */
    private function getReactivationUrl(): string
    {
        $baseUrl = config('app.frontend_url');
        return match($this->user->role) {
            'donor' => "$baseUrl/auth/retrieve/donor",
            'charity_admin' => "$baseUrl/auth/retrieve/charity",
            default => "$baseUrl/auth/login",
        };
    }
}
