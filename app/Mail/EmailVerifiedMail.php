<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerifiedMail extends Mailable implements ShouldQueue
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
            subject: '✅ Email Verified Successfully - Welcome to CharityHub!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.email-verified',
            with: [
                'userName' => $this->user->name,
                'userRole' => $this->user->role,
                'dashboardUrl' => $this->getDashboardUrl(),
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
     * Get dashboard URL based on user role
     */
    private function getDashboardUrl(): string
    {
        $baseUrl = config('app.frontend_url');
        return match($this->user->role) {
            'donor' => "$baseUrl/donor",
            'charity_admin' => "$baseUrl/charity",
            'admin' => "$baseUrl/admin",
            default => $baseUrl,
        };
    }
}
