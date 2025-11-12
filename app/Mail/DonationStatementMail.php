<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class DonationStatementMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $year;
    public $totalAmount;
    public $donationCount;
    public $campaignsSupported;
    public $pdfPath;

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $user,
        int $year,
        float $totalAmount,
        int $donationCount,
        int $campaignsSupported,
        string $pdfPath = null
    ) {
        $this->user = $user;
        $this->year = $year;
        $this->totalAmount = $totalAmount;
        $this->donationCount = $donationCount;
        $this->campaignsSupported = $campaignsSupported;
        $this->pdfPath = $pdfPath;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your {$this->year} Annual Donation Statement from CharityHub",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.donations.annual-statement',
            with: [
                'userName' => $this->user->name,
                'year' => $this->year,
                'totalAmount' => number_format($this->totalAmount, 2),
                'donationCount' => $this->donationCount,
                'campaignsSupported' => $this->campaignsSupported,
                'dashboardUrl' => config('app.frontend_url') . '/donor/donations',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        if ($this->pdfPath && file_exists(storage_path('app/' . $this->pdfPath))) {
            return [
                Attachment::fromStorage($this->pdfPath)
                    ->as("CharityHub_Donation_Statement_{$this->year}.pdf")
                    ->withMime('application/pdf'),
            ];
        }

        return [];
    }
}
