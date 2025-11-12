<?php

namespace App\Mail\Security;

use App\Models\User;
use App\Models\FailedLogin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FailedLoginAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $failedLogin;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, FailedLogin $failedLogin)
    {
        $this->user = $user;
        $this->failedLogin = $failedLogin;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🚨 Security Alert: Multiple Failed Login Attempts - CharityHub",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.security.failed-login-alert',
            with: [
                'userName' => $this->user->name,
                'attempts' => $this->failedLogin->attempts,
                'ipAddress' => $this->failedLogin->ip_address,
                'lastAttempt' => $this->failedLogin->last_attempt_at->format('F d, Y h:i A'),
                'changePasswordUrl' => config('app.frontend_url') . '/donor/settings',
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
