<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sender;
    public $recipient;
    public $message;
    public $messagePreview;

    /**
     * Create a new event instance.
     */
    public function __construct(
        User $sender,
        User $recipient,
        string $message,
        string $messagePreview = null
    ) {
        $this->sender = $sender;
        $this->recipient = $recipient;
        $this->message = $message;
        $this->messagePreview = $messagePreview ?? substr($message, 0, 100);
    }
}
