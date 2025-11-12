<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FailedLogin extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'ip_address',
        'user_agent',
        'attempts',
        'last_attempt_at',
        'alert_sent',
        'alert_sent_at',
    ];

    protected $casts = [
        'last_attempt_at' => 'datetime',
        'alert_sent_at' => 'datetime',
        'alert_sent' => 'boolean',
    ];

    /**
     * Get the user associated with the failed login
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
