<?php

namespace App\Listeners;

use App\Events\PaymentMethodUpdated;
use App\Mail\PaymentMethodUpdatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendPaymentMethodUpdatedEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PaymentMethodUpdated $event): void
    {
        try {
            Mail::to($event->user->email)->queue(
                new PaymentMethodUpdatedMail(
                    $event->user,
                    $event->action,
                    $event->paymentType,
                    $event->last4Digits,
                    $event->timestamp
                )
            );

            Log::info('Payment method update email queued', [
                'user_id' => $event->user->id,
                'user_email' => $event->user->email,
                'action' => $event->action,
                'payment_type' => $event->paymentType,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue payment method update email', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
