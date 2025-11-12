<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $status;
    public $reason;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $status, $reason = null)
    {
        $this->user = $user;
        $this->status = $status; // 'deactivated', 'reactivated', 'suspended'
        $this->reason = $reason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subjectMap = [
            'deactivated' => 'Account Deactivated',
            'reactivated' => 'Account Reactivated',
            'suspended' => 'Account Suspended',
        ];

        return new Envelope(
            subject: ($subjectMap[$this->status] ?? 'Account Status Changed') . ' - CharityConnect',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.account-status',
            with: [
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'status' => $this->status,
                'reason' => $this->reason,
                'statusDate' => now()->format('F d, Y h:i A'),
                'supportUrl' => config('app.frontend_url') . '/support',
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
