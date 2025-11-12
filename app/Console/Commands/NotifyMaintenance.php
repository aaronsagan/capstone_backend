<?php

namespace App\Console\Commands;

use App\Models\SystemNotification;
use App\Events\MaintenanceScheduled;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NotifyMaintenance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notify:maintenance {notification_id?}';

    /**
     * The console command description.
     */
    protected $description = 'Send maintenance notification emails to all active users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for maintenance notifications to send...');

        if ($this->argument('notification_id')) {
            // Send specific notification
            $notification = SystemNotification::find($this->argument('notification_id'));
            
            if (!$notification) {
                $this->error('Notification not found.');
                return 1;
            }
            
            if ($notification->type !== 'maintenance') {
                $this->error('Notification is not a maintenance type.');
                return 1;
            }
            
            $this->sendNotification($notification);
        } else {
            // Send all pending maintenance notifications
            $notifications = SystemNotification::where('type', 'maintenance')
                ->where('status', 'scheduled')
                ->where('email_sent', false)
                ->get();

            if ($notifications->isEmpty()) {
                $this->info('No pending maintenance notifications found.');
                return 0;
            }

            $this->info("Found {$notifications->count()} maintenance notifications.");

            foreach ($notifications as $notification) {
                // Check if should send (12 hours before start time)
                if ($notification->shouldSendEmail()) {
                    $this->sendNotification($notification);
                } else {
                    $hoursUntil = now()->diffInHours($notification->start_time, false);
                    $this->warn("Notification '{$notification->title}' not ready to send yet (starts in {$hoursUntil} hours)");
                }
            }
        }

        $this->info('Maintenance notification check complete.');
        return 0;
    }

    /**
     * Send notification
     */
    private function sendNotification(SystemNotification $notification)
    {
        try {
            event(new MaintenanceScheduled($notification));
            
            $this->info("✓ Notification sent: {$notification->title}");
            
            Log::info('Maintenance notification sent via command', [
                'notification_id' => $notification->id,
                'title' => $notification->title,
            ]);
        } catch (\Exception $e) {
            $this->error("✗ Failed to send: {$notification->title}");
            $this->error("  Error: {$e->getMessage()}");
            
            Log::error('Failed to send maintenance notification via command', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
