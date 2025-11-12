<?php

namespace App\Mail;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VideoRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $video;
    public $reason;

    /**
     * Create a new message instance.
     */
    public function __construct(Video $video, $reason)
    {
        $this->video = $video;
        $this->reason = $reason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '❌ Video Upload Rejected - CharityHub',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.videos.rejected',
            with: [
                'videoTitle' => $this->video->original_filename,
                'campaignTitle' => $this->video->campaign->title,
                'reason' => $this->reason,
                'uploadUrl' => config('app.frontend_url') . '/campaigns/' . $this->video->campaign_id . '/edit',
            ]
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
