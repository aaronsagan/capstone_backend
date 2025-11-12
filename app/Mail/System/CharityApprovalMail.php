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

class CharityApprovalMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $charity;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Charity $charity)
    {
        $this->user = $user;
        $this->charity = $charity;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🎉 Your Charity Account Has Been Approved! - CharityHub",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.system.charity-approval',
            with: [
                'userName' => $this->user->name,
                'charityName' => $this->charity->organization_name,
                'approvedAt' => now()->format('F d, Y'),
                'dashboardUrl' => config('app.frontend_url') . '/charity/dashboard',
                'createCampaignUrl' => config('app.frontend_url') . '/charity/campaigns/create',
                'profileUrl' => config('app.frontend_url') . '/charities/' . $this->charity->id,
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
