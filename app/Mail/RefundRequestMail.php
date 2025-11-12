<?php

namespace App\Mail;

use App\Models\{Donation, User, RefundRequest};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RefundRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $donation;
    public $refundRequest;
    public $recipient; // 'donor' or 'charity'

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Donation $donation, RefundRequest $refundRequest, string $recipient = 'donor')
    {
        $this->user = $user;
        $this->donation = $donation;
        $this->refundRequest = $refundRequest;
        $this->recipient = $recipient;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $campaignName = $this->donation->campaign 
            ? $this->donation->campaign->title 
            : ($this->donation->charity ? $this->donation->charity->name : 'Donation');

        if ($this->recipient === 'donor') {
            $subject = "Refund Request Confirmation - {$campaignName} - CharityHub";
        } else {
            $subject = "New Refund Request - {$campaignName} - Action Required - CharityHub";
        }

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $donor = $this->donation->donor ?? $this->refundRequest->user;
        $charity = $this->donation->charity;
        $campaign = $this->donation->campaign;

        return new Content(
            view: $this->recipient === 'donor' 
                ? 'emails.donations.refund-confirmation' 
                : 'emails.donations.refund-alert-charity',
            with: [
                'userName' => $this->user->name,
                'donorName' => $donor->name ?? $this->donation->donor_name ?? 'Donor',
                'campaignTitle' => $campaign ? $campaign->title : 'General Support',
                'charityName' => $charity ? $charity->name : 'Charity',
                'amount' => number_format($this->donation->amount, 2),
                'transactionId' => $this->donation->reference_number ?? $this->donation->id,
                'donationDate' => ($this->donation->donated_at ?? $this->donation->created_at)->format('F d, Y'),
                'refundReason' => $this->refundRequest->reason,
                'refundId' => $this->refundRequest->id,
                'requestDate' => $this->refundRequest->created_at->format('F d, Y h:i A'),
                'dashboardUrl' => $this->recipient === 'donor'
                    ? config('app.frontend_url') . '/donor/refunds'
                    : config('app.frontend_url') . '/charity/refunds',
                'donationId' => $this->donation->id,
                'hasProof' => !empty($this->refundRequest->proof_url),
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

