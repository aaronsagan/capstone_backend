<?php

namespace App\Events;

use App\Models\Charity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CharityApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $charity;

    /**
     * Create a new event instance.
     */
    public function __construct(Charity $charity)
    {
        $this->charity = $charity;
    }
}
