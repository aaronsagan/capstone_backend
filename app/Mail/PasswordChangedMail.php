<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $ipAddress;
    public $userAgent;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $ipAddress = '', string $userAgent = '')
    {
        $this->user = $user;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔒 Password Changed Successfully - CharityHub',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.password-changed',
            with: [
                'userName' => $this->user->name,
                'changedAt' => now()->format('F d, Y h:i A'),
                'ipAddress' => $this->ipAddress,
                'device' => $this->parseUserAgent($this->userAgent),
                'supportUrl' => config('app.frontend_url') . '/support',
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
     * Parse user agent to get readable device info
     */
    private function parseUserAgent($userAgent): string
    {
        if (empty($userAgent)) {
            return 'Unknown Device';
        }

        // Simple parsing for common browsers
        if (stripos($userAgent, 'Chrome') !== false) {
            return 'Chrome Browser';
        } elseif (stripos($userAgent, 'Firefox') !== false) {
            return 'Firefox Browser';
        } elseif (stripos($userAgent, 'Safari') !== false) {
            return 'Safari Browser';
        } elseif (stripos($userAgent, 'Edge') !== false) {
            return 'Edge Browser';
        }

        return 'Web Browser';
    }
}
