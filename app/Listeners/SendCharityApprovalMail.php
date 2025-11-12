<?php

namespace App\Listeners;

use App\Events\CharityApproved;
use App\Mail\System\CharityApprovalMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendCharityApprovalMail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CharityApproved $event): void
    {
        try {
            $charity = $event->charity;
            
            // Get charity admin user
            $admin = $charity->user;
            
            if (!$admin) {
                Log::warning('Charity has no associated user/admin', [
                    'charity_id' => $charity->id
                ]);
                return;
            }

            Mail::to($admin->email)->queue(
                new CharityApprovalMail($admin, $charity)
            );

            Log::info('Charity approval email queued', [
                'charity_id' => $charity->id,
                'admin_email' => $admin->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue charity approval email', [
                'charity_id' => $event->charity->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
