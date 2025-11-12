<?php

namespace App\Mail\Engagement;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportTicketAcknowledgmentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $ticket;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, $ticket)
    {
        $this->user = $user;
        $this->ticket = $ticket;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $ticketId = is_object($this->ticket) ? $this->ticket->id : ($this->ticket['id'] ?? 'Unknown');
        
        return new Envelope(
            subject: "Support Request Received — Ticket #{$ticketId} - CharityHub",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $ticketId = is_object($this->ticket) ? $this->ticket->id : ($this->ticket['id'] ?? 'Unknown');
        $subject = is_object($this->ticket) ? $this->ticket->subject : ($this->ticket['subject'] ?? 'Support Request');
        $message = is_object($this->ticket) ? $this->ticket->message : ($this->ticket['message'] ?? '');
        $createdAt = is_object($this->ticket) 
            ? $this->ticket->created_at->format('F d, Y h:i A') 
            : now()->format('F d, Y h:i A');
        
        return new Content(
            view: 'emails.engagement.support-ticket-acknowledgment',
            with: [
                'userName' => $this->user->name,
                'ticketId' => $ticketId,
                'ticketSubject' => $subject,
                'ticketMessage' => substr($message, 0, 300),
                'submittedAt' => $createdAt,
                'expectedResponseTime' => '24-48 hours',
                'ticketUrl' => config('app.frontend_url') . '/donor/support/tickets/' . $ticketId,
                'supportUrl' => config('app.frontend_url') . '/donor/support',
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
