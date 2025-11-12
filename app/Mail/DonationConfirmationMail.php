<?php

namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DonationConfirmationMail extends Mailable implements ShouldQueue
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
        $campaignName = $this->campaign 
            ? $this->campaign->title 
            : $this->charity->organization_name;

        return new Envelope(
            subject: "Thank you for your donation to {$campaignName}! - CharityHub",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.donations.confirmation',
            with: [
                'donorName' => $this->donor->name ?? $this->donation->donor_name ?? 'Valued Donor',
                'campaignTitle' => $this->campaign ? $this->campaign->title : 'General Support',
                'charityName' => $this->charity->organization_name,
                'amount' => number_format($this->donation->amount, 2),
                'transactionId' => $this->donation->reference_number ?? $this->donation->id,
                'donationDate' => $this->donation->donated_at->format('F d, Y h:i A'),
                'isAnonymous' => $this->donation->is_anonymous,
                'isRecurring' => $this->donation->is_recurring,
                'recurringType' => $this->donation->recurring_type,
                'dashboardUrl' => config('app.frontend_url') . '/donor/donations',
                'receiptNumber' => $this->donation->receipt_no ?? 'PENDING',
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
