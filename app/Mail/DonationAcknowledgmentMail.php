<?php

namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class DonationAcknowledgmentMail extends Mailable implements ShouldQueue
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
            subject: '📜 Your Official Donation Acknowledgment Letter - CharityHub',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.donations.acknowledgment',
            with: [
                'donorName' => $this->donor->name ?? $this->donation->donor_name ?? 'Valued Donor',
                'amount' => number_format($this->donation->amount, 2),
                'campaignTitle' => $this->campaign ? $this->campaign->title : 'General Support',
                'charityName' => $this->charity->organization_name,
                'transactionId' => $this->donation->reference_number ?? $this->donation->id,
                'receiptNumber' => $this->donation->receipt_no ?? 'PENDING',
                'donationDate' => $this->donation->donated_at->format('F d, Y h:i A'),
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
        // Generate PDF acknowledgment letter
        $pdf = $this->generateAcknowledgmentPDF();

        $filename = 'Acknowledgment_Letter_' . 
                    ($this->donation->reference_number ?? $this->donation->id) . 
                    '_' . date('Y-m-d') . '.pdf';

        return [
            Attachment::fromData(fn () => $pdf, $filename)
                ->withMime('application/pdf'),
        ];
    }

    /**
     * Generate PDF acknowledgment letter
     */
    private function generateAcknowledgmentPDF()
    {
        $data = [
            'donorName' => $this->donor->name ?? $this->donation->donor_name ?? 'Valued Donor',
            'amount' => $this->donation->amount,
            'campaignTitle' => $this->campaign ? $this->campaign->title : 'General Support',
            'charityName' => $this->charity->organization_name,
            'transactionId' => $this->donation->reference_number ?? $this->donation->id,
            'receiptNumber' => $this->donation->receipt_no ?? 'PENDING',
            'donationDate' => $this->donation->donated_at,
            'verifiedDate' => now(),
            'charityRepresentative' => $this->charity->contact_person ?? $this->charity->owner->name,
            'charityRole' => 'Authorized Representative',
        ];

        $pdf = Pdf::loadView('pdf.acknowledgment-letter', $data);
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->output();
    }
}
