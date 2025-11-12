<?php

namespace App\Listeners;

use App\Events\SupportTicketCreated;
use App\Mail\Engagement\SupportTicketAcknowledgmentMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendSupportTicketAcknowledgment implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(SupportTicketCreated $event): void
    {
        try {
            Mail::to($event->user->email)->queue(
                new SupportTicketAcknowledgmentMail($event->user, $event->ticket)
            );

            Log::info('Support ticket acknowledgment email queued', [
                'user_id' => $event->user->id,
                'ticket_id' => $event->ticket->id ?? $event->ticket['id'] ?? 'unknown'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue support ticket acknowledgment email', [
                'user_id' => $event->user->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }
}
