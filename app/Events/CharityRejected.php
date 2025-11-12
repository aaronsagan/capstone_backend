<?php

namespace App\Events;

use App\Models\Charity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CharityRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $charity;
    public $reason;

    /**
     * Create a new event instance.
     */
    public function __construct(Charity $charity, ?string $reason = null)
    {
        $this->charity = $charity;
        $this->reason = $reason ?? $charity->rejection_reason ?? 'Application did not meet our requirements.';
    }
}
