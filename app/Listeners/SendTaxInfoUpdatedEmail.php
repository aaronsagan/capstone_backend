<?php

namespace App\Listeners;

use App\Events\TaxInfoUpdated;
use App\Mail\TaxInfoUpdatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendTaxInfoUpdatedEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(TaxInfoUpdated $event): void
    {
        try {
            Mail::to($event->user->email)->queue(
                new TaxInfoUpdatedMail(
                    $event->user,
                    $event->taxInfo,
                    $event->timestamp
                )
            );

            Log::info('Tax info update email queued', [
                'user_id' => $event->user->id,
                'user_email' => $event->user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue tax info update email', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
