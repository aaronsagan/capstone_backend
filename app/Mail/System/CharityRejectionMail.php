<?php

namespace App\Mail\System;

use App\Models\User;
use App\Models\Charity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CharityRejectionMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $charity;
    public $reason;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Charity $charity, string $reason)
    {
        $this->user = $user;
        $this->charity = $charity;
        $this->reason = $reason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Charity Account Application Update - CharityHub",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.system.charity-rejection',
            with: [
                'userName' => $this->user->name,
                'charityName' => $this->charity->organization_name,
                'reason' => $this->reason,
                'reviewedAt' => now()->format('F d, Y'),
                'resubmitUrl' => config('app.frontend_url') . '/auth/register-charity',
                'supportUrl' => config('app.frontend_url') . '/support',
                'guidelinesUrl' => config('app.frontend_url') . '/charity-guidelines',
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
