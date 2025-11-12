<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SecurityAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $attemptCount;
    public $ipAddress;
    public $lockUntil;
    public $lockoutMinutes;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, int $attemptCount, string $ipAddress, $lockUntil, int $lockoutMinutes)
    {
        $this->user = $user;
        $this->attemptCount = $attemptCount;
        $this->ipAddress = $ipAddress;
        $this->lockUntil = $lockUntil;
        $this->lockoutMinutes = $lockoutMinutes;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Security Alert - Multiple Failed Login Attempts',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.security-alert',
            with: [
                'userName' => $this->user->name,
                'attemptCount' => $this->attemptCount,
                'ipAddress' => $this->ipAddress,
                'lockUntil' => $this->lockUntil->format('M d, Y h:i A'),
                'lockoutMinutes' => $this->lockoutMinutes,
                'resetPasswordUrl' => url('/password/reset'),
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
}
