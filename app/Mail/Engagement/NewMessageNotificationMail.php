<?php

namespace App\Mail\Engagement;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewMessageNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $recipient;
    public $sender;
    public $messagePreview;

    /**
     * Create a new message instance.
     */
    public function __construct(User $recipient, User $sender, string $messagePreview)
    {
        $this->recipient = $recipient;
        $this->sender = $sender;
        $this->messagePreview = $messagePreview;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New message from {$this->sender->name} - CharityHub",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.engagement.new-message-notification',
            with: [
                'recipientName' => $this->recipient->name,
                'senderName' => $this->sender->name,
                'senderEmail' => $this->sender->email,
                'messagePreview' => $this->messagePreview,
                'timestamp' => now()->format('F d, Y h:i A'),
                'messagesUrl' => config('app.frontend_url') . '/messages',
                'replyUrl' => config('app.frontend_url') . '/messages?reply_to=' . $this->sender->id,
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
