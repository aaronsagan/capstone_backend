<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class TaxInfoUpdatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $taxInfo;
    public $timestamp;

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $user,
        array $taxInfo,
        ?Carbon $timestamp = null
    ) {
        $this->user = $user;
        $this->taxInfo = $taxInfo;
        $this->timestamp = $timestamp ?? now();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Tax Information Updated Successfully - CharityHub",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.tax.info-updated',
            with: [
                'userName' => $this->user->name,
                'tin' => $this->taxInfo['tin'] ?? 'N/A',
                'businessName' => $this->taxInfo['business_name'] ?? $this->taxInfo['businessName'] ?? 'N/A',
                'address' => $this->taxInfo['address'] ?? 'N/A',
                'timestamp' => $this->timestamp->format('F d, Y h:i A'),
                'dashboardUrl' => config('app.frontend_url') . '/donor/tax-info',
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
