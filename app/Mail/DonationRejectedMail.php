<?php

namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DonationRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $donation;
    public $donor;
    public $campaign;
    public $charity;
    public $reason;

    /**
     * Create a new message instance.
     */
    public function __construct(Donation $donation, string $reason = '')
    {
        $this->donation = $donation;
        $this->donor = $donation->donor;
        $this->campaign = $donation->campaign;
        $this->charity = $donation->charity;
        $this->reason = $reason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Donation Proof Rejected - Action Required',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.donations.rejected',
            with: [
                'donorName' => $this->donor->name ?? $this->donation->donor_name ?? 'Valued Donor',
                'amount' => number_format($this->donation->amount, 2),
                'campaignTitle' => $this->campaign ? $this->campaign->title : 'General Support',
                'charityName' => $this->charity->organization_name,
                'reason' => $this->reason ?: 'Invalid or unclear proof of payment',
                'transactionId' => $this->donation->reference_number ?? $this->donation->id,
                'resubmitUrl' => config('app.frontend_url') . '/donor/history',
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
