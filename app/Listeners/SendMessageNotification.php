<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Mail\Engagement\NewMessageNotificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendMessageNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        try {
            Mail::to($event->recipient->email)->queue(
                new NewMessageNotificationMail(
                    $event->recipient,
                    $event->sender,
                    $event->messagePreview
                )
            );

            Log::info('Message notification email queued', [
                'sender_id' => $event->sender->id,
                'recipient_id' => $event->recipient->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue message notification email', [
                'recipient_id' => $event->recipient->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }
}
