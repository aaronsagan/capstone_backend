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
use Carbon\Carbon;

class CampaignReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $donor;
    public $campaign;

    /**
     * Create a new message instance.
     */
    public function __construct(User $donor, Campaign $campaign)
    {
        $this->donor = $donor;
        $this->campaign = $campaign;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reminder: '{$this->campaign->title}' is ending soon! - CharityHub",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $endDate = Carbon::parse($this->campaign->end_date);
        $daysRemaining = now()->diffInDays($endDate);
        
        return new Content(
            view: 'emails.engagement.campaign-reminder',
            with: [
                'donorName' => $this->donor->name,
                'campaignTitle' => $this->campaign->title,
                'campaignDescription' => substr($this->campaign->description ?? $this->campaign->problem ?? '', 0, 200),
                'campaignImage' => $this->campaign->cover_image_path,
                'goalAmount' => number_format($this->campaign->target_amount, 2),
                'currentAmount' => number_format($this->campaign->current_amount ?? 0, 2),
                'percentageFunded' => round((($this->campaign->current_amount ?? 0) / $this->campaign->target_amount) * 100, 1),
                'daysRemaining' => $daysRemaining,
                'endDate' => $endDate->format('F d, Y'),
                'campaignUrl' => config('app.frontend_url') . '/campaigns/' . $this->campaign->id,
                'donateUrl' => config('app.frontend_url') . '/campaigns/' . $this->campaign->id . '/donate',
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
