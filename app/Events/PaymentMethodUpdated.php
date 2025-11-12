<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentMethodUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $action; // 'added', 'removed', 'changed'
    public $paymentType; // 'GCash', 'PayPal', 'Credit Card', etc.
    public $last4Digits; // Last 4 digits if applicable
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(
        User $user,
        string $action,
        string $paymentType,
        ?string $last4Digits = null
    ) {
        $this->user = $user;
        $this->action = $action;
        $this->paymentType = $paymentType;
        $this->last4Digits = $last4Digits;
        $this->timestamp = now();
    }
}
