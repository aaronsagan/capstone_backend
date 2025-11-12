<?php

namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewDonationAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $donation;
    public $donor;
    public $campaign;
    public $charity;

    /**
     * Create a new message instance.
     */
    public function __construct(Donation $donation)
    {
        $this->donation = $donation;
        $this->donor = $donation->donor;
        $this->campaign = $donation->campaign;
        $this->charity = $donation->charity;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $donorName = $this->donation->is_anonymous 
            ? 'An Anonymous Donor' 
            : ($this->donor->name ?? $this->donation->donor_name ?? 'A Donor');

        return new Envelope(
            subject: "New Donation Received — {$donorName} just donated to your campaign! - CharityHub",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.donations.new-donation-alert',
            with: [
                'donorName' => $this->donation->is_anonymous 
                    ? 'An Anonymous Donor' 
                    : ($this->donor->name ?? $this->donation->donor_name ?? 'A Donor'),
                'campaignTitle' => $this->campaign ? $this->campaign->title : 'General Support',
                'charityName' => $this->charity->organization_name,
                'amount' => number_format($this->donation->amount, 2),
                'donationDate' => $this->donation->donated_at->format('F d, Y h:i A'),
                'isAnonymous' => $this->donation->is_anonymous,
                'isRecurring' => $this->donation->is_recurring,
                'recurringType' => $this->donation->recurring_type,
                'donationMessage' => $this->donation->message,
                'dashboardUrl' => config('app.frontend_url') . '/charity/donations',
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
