<?php

namespace App\Mail;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewCampaignNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $campaign;
    public $follower;

    /**
     * Create a new message instance.
     */
    public function __construct(Campaign $campaign, User $follower)
    {
        $this->campaign = $campaign;
        $this->follower = $follower;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $charityName = $this->campaign->charity->organization_name;
        return new Envelope(
            subject: "✨ New Campaign from {$charityName} - CharityHub",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.engagement.new-campaign',
            with: [
                'followerName' => $this->follower->name,
                'campaignTitle' => $this->campaign->title,
                'campaignDescription' => substr(strip_tags($this->campaign->description ?? 'Support this important cause!'), 0, 200) . '...',
                'charityName' => $this->campaign->charity->organization_name,
                'goalAmount' => number_format($this->campaign->target_amount, 2),
                'endDate' => $this->campaign->end_date ? $this->campaign->end_date->format('F d, Y') : 'Ongoing',
                'campaignUrl' => config('app.frontend_url') . '/campaigns/' . $this->campaign->id,
                'charityUrl' => config('app.frontend_url') . '/donor/charities/' . $this->campaign->charity_id,
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
