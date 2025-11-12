<?php

namespace App\Mail\Engagement;

use App\Models\User;
use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignProgressMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $donor;
    public $campaign;
    public $percentage;
    public $milestone;

    /**
     * Create a new message instance.
     */
    public function __construct(User $donor, Campaign $campaign, int $percentage, int $milestone)
    {
        $this->donor = $donor;
        $this->campaign = $campaign;
        $this->percentage = $percentage;
        $this->milestone = $milestone;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Great news! '{$this->campaign->title}' is now {$this->milestone}% funded! - CharityHub",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.engagement.campaign-progress',
            with: [
                'donorName' => $this->donor->name,
                'campaignTitle' => $this->campaign->title,
                'charityName' => $this->campaign->charity->organization_name ?? 'Our Charity',
                'campaignImage' => $this->campaign->cover_image_path,
                'milestone' => $this->milestone,
                'percentage' => $this->percentage,
                'goalAmount' => number_format($this->campaign->target_amount, 2),
                'currentAmount' => number_format($this->campaign->current_amount ?? 0, 2),
                'remainingAmount' => number_format($this->campaign->target_amount - ($this->campaign->current_amount ?? 0), 2),
                'campaignUrl' => config('app.frontend_url') . '/campaigns/' . $this->campaign->id,
                'shareUrl' => config('app.frontend_url') . '/campaigns/' . $this->campaign->id . '/share',
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
