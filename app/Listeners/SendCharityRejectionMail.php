<?php

namespace App\Listeners;

use App\Events\CharityRejected;
use App\Mail\System\CharityRejectionMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendCharityRejectionMail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CharityRejected $event): void
    {
        try {
            $charity = $event->charity;
            $reason = $event->reason;
            
            // Get charity admin user
            $admin = $charity->user;
            
            if (!$admin) {
                Log::warning('Charity has no associated user/admin', [
                    'charity_id' => $charity->id
                ]);
                return;
            }

            Mail::to($admin->email)->queue(
                new CharityRejectionMail($admin, $charity, $reason)
            );

            Log::info('Charity rejection email queued', [
                'charity_id' => $charity->id,
                'admin_email' => $admin->email,
                'reason' => $reason,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue charity rejection email', [
                'charity_id' => $event->charity->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
