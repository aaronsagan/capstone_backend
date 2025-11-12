<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CharityReactivationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $charity;
    public $token;
    public $reactivateUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $charity, $token)
    {
        $this->user = $user;
        $this->charity = $charity;
        $this->token = $token;
        $this->reactivateUrl = config('app.frontend_url') . "/auth/reactivate-account?token={$token}&type=charity";
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Charity Account Reactivation Request - CharityConnect',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.reactivate-charity',
            with: [
                'userName' => $this->user->name,
                'organizationName' => $this->charity->organization_name ?? 'Your Organization',
                'userEmail' => $this->user->email,
                'reactivateUrl' => $this->reactivateUrl,
                'requestDate' => now()->format('F d, Y h:i A'),
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
