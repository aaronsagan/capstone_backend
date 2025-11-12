<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaxInfoUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $taxInfo; // Array of tax information
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, array $taxInfo)
    {
        $this->user = $user;
        $this->taxInfo = $taxInfo;
        $this->timestamp = now();
    }
}
