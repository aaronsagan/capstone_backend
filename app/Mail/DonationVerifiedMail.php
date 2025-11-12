<?php

namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DonationVerifiedMail extends Mailable implements ShouldQueue
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
        return new Envelope(
            subject: '✅ Your Donation Has Been Verified - CharityHub',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.donations.verified',
            with: [
                'donorName' => $this->donor->name ?? $this->donation->donor_name ?? 'Valued Donor',
                'amount' => number_format($this->donation->amount, 2),
                'campaignTitle' => $this->campaign ? $this->campaign->title : 'General Support',
                'charityName' => $this->charity->organization_name,
                'transactionId' => $this->donation->reference_number ?? $this->donation->id,
                'verifiedDate' => now()->format('F d, Y h:i A'),
                'dashboardUrl' => config('app.frontend_url') . '/donor/history',
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
