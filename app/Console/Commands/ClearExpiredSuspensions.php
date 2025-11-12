<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\NotificationHelper;

class ClearExpiredSuspensions extends Command
{
    protected $signature = 'app:clear-expired-suspensions';
    protected $description = 'Clear expired suspensions and reactivate accounts';

    public function handle()
    {
        $expiredSuspensions = User::where('status', 'suspended')
            ->where('suspended_until', '<=', now())
            ->get();

        $count = 0;
        foreach ($expiredSuspensions as $user) {
            $user->update([
                'status' => 'active',
                'suspended_until' => null,
                'suspension_reason' => null,
                'suspension_level' => null,
            ]);

            // Notify user of reactivation
            NotificationHelper::accountReactivated($user);

            // Send email
            try {
                $fromAddress = config('mail.from.address');
                $fromName = config('mail.from.name');
                \Mail::send('emails.system-alert', [
                    'user_name' => $user->name,
                    'message' => "Your account has been reactivated. The suspension period has ended and you can now access all features.",
                    'type' => 'success'
                ], function($mail) use ($user, $fromAddress, $fromName) {
                    if ($fromAddress) $mail->from($fromAddress, $fromName ?: config('app.name'));
                    $mail->to($user->email)->subject('Account Reactivated');
                });
            } catch (\Throwable $e) {
                \Log::error('Failed to send reactivation email', ['user' => $user->id, 'error' => $e->getMessage()]);
            }

            $count++;
        }

        $this->info("Cleared {$count} expired suspensions");
        return 0;
    }
}
