<?php

namespace App\Mail\Engagement;

use App\Models\User;
use App\Models\Campaign;
use App\Models\Charity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CharityUpdateNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $donor;
    public $campaign;
    public $charity;

    /**
     * Create a new message instance.
     */
    public function __construct(User $donor, Campaign $campaign, Charity $charity)
    {
        $this->donor = $donor;
        $this->campaign = $campaign;
        $this->charity = $charity;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Update from {$this->charity->organization_name}! - CharityHub",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.engagement.charity-update',
            with: [
                'donorName' => $this->donor->name,
                'charityName' => $this->charity->organization_name,
                'campaignTitle' => $this->campaign->title,
                'campaignDescription' => $this->campaign->description ?? substr($this->campaign->problem ?? '', 0, 200),
                'campaignImage' => $this->campaign->cover_image_path,
                'goalAmount' => number_format($this->campaign->target_amount, 2),
                'campaignUrl' => config('app.frontend_url') . '/campaigns/' . $this->campaign->id,
                'charityUrl' => config('app.frontend_url') . '/charities/' . $this->charity->id,
                'unsubscribeUrl' => config('app.frontend_url') . '/donor/settings/notifications',
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
