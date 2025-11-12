<?php

namespace App\Events;

use App\Models\Campaign;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignProgressUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $campaign;
    public $percentage;
    public $milestone; // 50, 80, 100

    /**
     * Create a new event instance.
     */
    public function __construct(Campaign $campaign, int $percentage, int $milestone)
    {
        $this->campaign = $campaign;
        $this->percentage = $percentage;
        $this->milestone = $milestone;
    }
}
