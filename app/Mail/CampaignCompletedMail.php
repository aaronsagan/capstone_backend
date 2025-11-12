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

class CampaignCompletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $campaign;
    public $donor;
    public $totalRaised;
    public $donorCount;

    /**
     * Create a new message instance.
     */
    public function __construct(Campaign $campaign, User $donor = null)
    {
        $this->campaign = $campaign;
        $this->donor = $donor;
        $this->totalRaised = $campaign->current_amount;
        $this->donorCount = $campaign->donations()->distinct('donor_id')->count();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎉 Campaign Goal Reached - Thank You for Your Support!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.engagement.campaign-completed',
            with: [
                'donorName' => $this->donor->name ?? 'Valued Donor',
                'campaignTitle' => $this->campaign->title,
                'charityName' => $this->campaign->charity->organization_name,
                'goalAmount' => number_format($this->campaign->target_amount, 2),
                'totalRaised' => number_format($this->totalRaised, 2),
                'donorCount' => $this->donorCount,
                'completedDate' => now()->format('F d, Y'),
                'campaignUrl' => config('app.frontend_url') . '/campaigns/' . $this->campaign->id,
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
