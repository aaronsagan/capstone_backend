<?php

namespace App\Mail\System;

use App\Models\User;
use App\Models\SystemNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MaintenanceNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $notification;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, SystemNotification $notification)
    {
        $this->user = $user;
        $this->notification = $notification;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ Scheduled Maintenance Notice - CharityHub",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $duration = $this->notification->start_time && $this->notification->end_time
            ? $this->notification->start_time->diffInMinutes($this->notification->end_time)
            : null;

        return new Content(
            view: 'emails.system.maintenance-notification',
            with: [
                'userName' => $this->user->name,
                'title' => $this->notification->title,
                'message' => $this->notification->message,
                'startTime' => $this->notification->start_time 
                    ? $this->notification->start_time->format('F d, Y h:i A') 
                    : 'TBD',
                'endTime' => $this->notification->end_time 
                    ? $this->notification->end_time->format('F d, Y h:i A') 
                    : 'TBD',
                'duration' => $duration 
                    ? ($duration >= 60 ? round($duration / 60, 1) . ' hours' : $duration . ' minutes')
                    : 'TBD',
                'statusPageUrl' => config('app.frontend_url') . '/status',
                'homeUrl' => config('app.frontend_url'),
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
