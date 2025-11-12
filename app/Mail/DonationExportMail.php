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

class DonationExportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $exportType; // 'csv' or 'pdf'
    public $filePath;
    public $recordCount;

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $user,
        string $exportType,
        string $filePath,
        int $recordCount
    ) {
        $this->user = $user;
        $this->exportType = strtoupper($exportType);
        $this->filePath = $filePath;
        $this->recordCount = $recordCount;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Donation Export is Ready - CharityHub",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.donations.export-ready',
            with: [
                'userName' => $this->user->name,
                'exportType' => $this->exportType,
                'recordCount' => $this->recordCount,
                'generatedDate' => now()->format('F d, Y h:i A'),
                'dashboardUrl' => config('app.frontend_url') . '/donor/donations',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        if ($this->filePath && file_exists(storage_path('app/' . $this->filePath))) {
            $extension = strtolower($this->exportType);
            $mimeType = $extension === 'csv' ? 'text/csv' : 'application/pdf';

            return [
                Attachment::fromStorage($this->filePath)
                    ->as("CharityHub_Donation_History." . $extension)
                    ->withMime($mimeType),
            ];
        }

        return [];
    }
}
