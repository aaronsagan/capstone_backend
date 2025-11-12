<?php

namespace App\Services;

use App\Models\User;
use App\Models\FailedLoginAttempt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\SecurityAlertMail;

class LoginAttemptService
{
    // Configuration constants
    const MAX_ATTEMPTS = 5;
    const TIME_FRAME_MINUTES = 10;
    const LOCKOUT_MINUTES = 15;
    const IP_MAX_ATTEMPTS = 20; // Higher threshold for IP-based blocking

    /**
     * Record a failed login attempt
     */
    public function recordFailedAttempt(string $email, string $ipAddress, ?string $userAgent = null): void
    {
        // Find user by email
        $user = User::where('email', $email)->first();

        // Record the attempt
        FailedLoginAttempt::record(
            $user?->id,
            $email,
            $ipAddress,
            $userAgent
        );

        // Check if lockout threshold is reached
        if ($user) {
            $this->checkAndLockAccount($user, $email, $ipAddress);
        }

        // Also check for IP-based abuse
        $this->checkIPAbuse($ipAddress);
    }

    /**
     * Check if account should be locked and lock if necessary
     */
    protected function checkAndLockAccount(User $user, string $email, string $ipAddress): void
    {
        $attemptCount = FailedLoginAttempt::getRecentAttempts($email, self::TIME_FRAME_MINUTES);

        if ($attemptCount >= self::MAX_ATTEMPTS) {
            $this->lockAccount($user, $attemptCount, $ipAddress);
        }
    }

    /**
     * Lock a user account
     */
    public function lockAccount(User $user, int $attemptCount, string $ipAddress): void
    {
        $lockUntil = now()->addMinutes(self::LOCKOUT_MINUTES);

        $user->update([
            'is_locked' => true,
            'locked_until' => $lockUntil,
            'failed_login_count' => $attemptCount,
            'last_failed_login' => now(),
        ]);

        // Send security alert email
        $this->sendSecurityAlert($user, $attemptCount, $ipAddress, $lockUntil);

        // Log the lockout event
        Log::warning("Account locked due to failed login attempts", [
            'user_id' => $user->id,
            'email' => $user->email,
            'attempt_count' => $attemptCount,
            'ip_address' => $ipAddress,
            'locked_until' => $lockUntil->toDateTimeString(),
        ]);
    }

    /**
     * Check if account is currently locked
     */
    public function isAccountLocked(User $user): bool
    {
        // If not locked, return false
        if (!$user->is_locked) {
            return false;
        }

        // If locked but time has expired, automatically unlock
        if ($user->locked_until && now()->greaterThan($user->locked_until)) {
            $this->unlockAccount($user);
            return false;
        }

        return true;
    }

    /**
     * Get remaining lockout time in minutes
     */
    public function getRemainingLockoutTime(User $user): ?int
    {
        if (!$user->is_locked || !$user->locked_until) {
            return null;
        }

        $remaining = now()->diffInMinutes($user->locked_until, false);
        
        return $remaining > 0 ? (int) ceil($remaining) : 0;
    }

    /**
     * Unlock a user account
     */
    public function unlockAccount(User $user): void
    {
        $user->update([
            'is_locked' => false,
            'locked_until' => null,
            'failed_login_count' => 0,
        ]);

        Log::info("Account unlocked", [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    /**
     * Manually unlock account (admin action)
     */
    public function manualUnlock(User $user, int $adminId): void
    {
        $this->unlockAccount($user);

        Log::warning("Account manually unlocked by admin", [
            'user_id' => $user->id,
            'email' => $user->email,
            'admin_id' => $adminId,
        ]);
    }

    /**
     * Clear failed attempts after successful login
     */
    public function clearFailedAttempts(User $user): void
    {
        // Only clear the count, keep history for audit
        $user->update([
            'failed_login_count' => 0,
            'last_failed_login' => null,
        ]);
    }

    /**
     * Check for IP-based abuse
     */
    protected function checkIPAbuse(string $ipAddress): void
    {
        $ipAttempts = FailedLoginAttempt::getRecentAttemptsByIP($ipAddress, self::TIME_FRAME_MINUTES);

        if ($ipAttempts >= self::IP_MAX_ATTEMPTS) {
            Log::critical("Potential brute force attack from IP", [
                'ip_address' => $ipAddress,
                'attempt_count' => $ipAttempts,
                'time_frame' => self::TIME_FRAME_MINUTES . ' minutes',
            ]);

            // Here you could implement IP blocking logic
            // For now, we just log it for admin review
        }
    }

    /**
     * Send security alert email
     */
    protected function sendSecurityAlert(User $user, int $attemptCount, string $ipAddress, $lockUntil): void
    {
        try {
            Mail::to($user->email)->send(new SecurityAlertMail(
                $user,
                $attemptCount,
                $ipAddress,
                $lockUntil,
                self::LOCKOUT_MINUTES
            ));

            Log::info("Security alert email sent", [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send security alert email", [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get failed login history for a user
     */
    public function getFailedLoginHistory(User $user, int $days = 7): array
    {
        $attempts = FailedLoginAttempt::where('user_id', $user->id)
            ->where('attempted_at', '>=', now()->subDays($days))
            ->orderBy('attempted_at', 'desc')
            ->get();

        return $attempts->map(function ($attempt) {
            return [
                'id' => $attempt->id,
                'ip_address' => $attempt->ip_address,
                'user_agent' => $attempt->user_agent,
                'attempted_at' => $attempt->attempted_at->toDateTimeString(),
            ];
        })->toArray();
    }

    /**
     * Get all recent failed login attempts (admin use)
     */
    public function getAllRecentAttempts(int $hours = 24, int $perPage = 50): array
    {
        $attempts = FailedLoginAttempt::with('user')
            ->where('attempted_at', '>=', now()->subHours($hours))
            ->orderBy('attempted_at', 'desc')
            ->paginate($perPage);

        return [
            'data' => $attempts->items(),
            'total' => $attempts->total(),
            'per_page' => $attempts->perPage(),
            'current_page' => $attempts->currentPage(),
            'last_page' => $attempts->lastPage(),
        ];
    }

    /**
     * Get locked accounts
     */
    public function getLockedAccounts(): array
    {
        return User::where('is_locked', true)
            ->where(function ($query) {
                $query->whereNull('locked_until')
                    ->orWhere('locked_until', '>', now());
            })
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'role' => $user->role,
                    'locked_until' => $user->locked_until?->toDateTimeString(),
                    'failed_login_count' => $user->failed_login_count,
                    'remaining_minutes' => $this->getRemainingLockoutTime($user),
                ];
            })
            ->toArray();
    }
}
