<?php

namespace App\Mail;

use App\Models\{Donation, User, RefundRequest};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RefundResponseMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $donation;
    public $refundRequest;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Donation $donation, RefundRequest $refundRequest)
    {
        $this->user = $user;
        $this->donation = $donation;
        $this->refundRequest = $refundRequest;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $campaignName = $this->donation->campaign 
            ? $this->donation->campaign->title 
            : ($this->donation->charity ? $this->donation->charity->name : 'Donation');

        $status = ucfirst($this->refundRequest->status);
        $subject = "Refund Request {$status} - {$campaignName} - CharityHub";

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $charity = $this->donation->charity;
        $campaign = $this->donation->campaign;

        return new Content(
            view: 'emails.donations.refund-response',
            with: [
                'userName' => $this->user->name,
                'campaignTitle' => $campaign ? $campaign->title : 'General Support',
                'charityName' => $charity ? $charity->name : 'Charity',
                'amount' => number_format($this->donation->amount, 2),
                'transactionId' => $this->donation->reference_number ?? $this->donation->id,
                'donationDate' => ($this->donation->donated_at ?? $this->donation->created_at)->format('F d, Y'),
                'refundReason' => $this->refundRequest->reason,
                'refundId' => $this->refundRequest->id,
                'status' => $this->refundRequest->status,
                'charityResponse' => $this->refundRequest->charity_response,
                'reviewedAt' => $this->refundRequest->reviewed_at->format('F d, Y h:i A'),
                'dashboardUrl' => config('app.frontend_url') . '/donor/refunds',
                'charityContactUrl' => config('app.frontend_url') . '/donor/charities/' . $charity->id,
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
