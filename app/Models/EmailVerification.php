<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'token',
        'code',
        'expires_at',
        'attempts',
        'resend_count',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Check if the verification has expired
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if max attempts reached
     */
    public function hasMaxAttempts()
    {
        return $this->attempts >= 5;
    }

    /**
     * Check if max resends reached
     */
    public function hasMaxResends()
    {
        return $this->resend_count >= 3;
    }

    /**
     * Increment attempts
     */
    public function incrementAttempts()
    {
        $this->increment('attempts');
    }

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
