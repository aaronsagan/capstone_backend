<?php

namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecurringDonationUpdateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $donation;
    public $donor;
    public $campaign;
    public $charity;
    public $action; // 'paused', 'resumed', 'cancelled'

    /**
     * Create a new message instance.
     */
    public function __construct(Donation $donation, string $action)
    {
        $this->donation = $donation;
        $this->donor = $donation->donor;
        $this->campaign = $donation->campaign;
        $this->charity = $donation->charity;
        $this->action = $action;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $actionTitle = ucfirst($this->action);
        
        return new Envelope(
            subject: "Your recurring donation has been {$actionTitle} - CharityHub",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.donations.recurring-update',
            with: [
                'donorName' => $this->donor->name,
                'campaignTitle' => $this->campaign ? $this->campaign->title : 'General Support',
                'charityName' => $this->charity->organization_name,
                'amount' => number_format($this->donation->amount, 2),
                'action' => $this->action,
                'actionTitle' => ucfirst($this->action),
                'recurringType' => $this->donation->recurring_type,
                'nextBillingDate' => $this->action === 'resumed' && $this->donation->next_donation_date
                    ? $this->donation->next_donation_date->format('F d, Y')
                    : null,
                'manageUrl' => config('app.frontend_url') . '/donor/recurring-donations',
                'donationId' => $this->donation->id,
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
