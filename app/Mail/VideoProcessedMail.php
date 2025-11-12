<?php

namespace App\Mail;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VideoProcessedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $video;

    /**
     * Create a new message instance.
     */
    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Video Processed Successfully - CharityHub',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.videos.processed',
            with: [
                'videoTitle' => $this->video->original_filename,
                'campaignTitle' => $this->video->campaign->title,
                'duration' => $this->video->formatted_duration,
                'size' => $this->video->formatted_size,
                'campaignUrl' => config('app.frontend_url') . '/campaigns/' . $this->video->campaign_id,
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
