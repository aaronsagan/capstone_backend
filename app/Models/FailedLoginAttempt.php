<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FailedLoginAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'ip_address',
        'user_agent',
        'attempted_at',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
    ];

    /**
     * Get the user that owns the failed login attempt
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get recent failed attempts for an email within time frame
     */
    public static function getRecentAttempts(string $email, int $minutes = 10): int
    {
        return static::where('email', $email)
            ->where('attempted_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Get recent failed attempts for an IP within time frame
     */
    public static function getRecentAttemptsByIP(string $ipAddress, int $minutes = 10): int
    {
        return static::where('ip_address', $ipAddress)
            ->where('attempted_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Clear old failed attempts (older than 24 hours)
     */
    public static function clearOldAttempts(): int
    {
        return static::where('attempted_at', '<', now()->subHours(24))->delete();
    }

    /**
     * Record a failed login attempt
     */
    public static function record(?int $userId, string $email, string $ipAddress, ?string $userAgent = null): self
    {
        return static::create([
            'user_id' => $userId,
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'attempted_at' => now(),
        ]);
    }
}
