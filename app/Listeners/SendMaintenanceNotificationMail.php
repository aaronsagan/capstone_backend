<?php

namespace App\Listeners;

use App\Events\MaintenanceScheduled;
use App\Mail\System\MaintenanceNotificationMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendMaintenanceNotificationMail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(MaintenanceScheduled $event): void
    {
        try {
            $notification = $event->notification;

            // Get all active users
            $users = User::where('status', 'active')->get();

            if ($users->isEmpty()) {
                Log::info('No active users to notify for maintenance');
                return;
            }

            // Send email to each user
            foreach ($users as $user) {
                Mail::to($user->email)->queue(
                    new MaintenanceNotificationMail($user, $notification)
                );
            }

            // Mark as sent
            $notification->update([
                'email_sent' => true,
                'email_sent_at' => now(),
            ]);

            Log::info('Maintenance notification emails queued', [
                'notification_id' => $notification->id,
                'users_count' => $users->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue maintenance notification emails', [
                'notification_id' => $event->notification->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
