<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'message',
        'start_time',
        'end_time',
        'status',
        'email_sent',
        'email_sent_at',
        'metadata',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'email_sent' => 'boolean',
        'email_sent_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Check if notification is currently active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' 
            && now()->between($this->start_time, $this->end_time);
    }

    /**
     * Check if notification should trigger email
     */
    public function shouldSendEmail(): bool
    {
        return !$this->email_sent 
            && $this->start_time 
            && now()->diffInHours($this->start_time, false) <= 12
            && now()->diffInHours($this->start_time, false) >= 0;
    }
}
