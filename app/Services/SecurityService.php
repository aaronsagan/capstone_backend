<?php

namespace App\Services;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TooManyLoginAttempts;
use Carbon\Carbon;

class SecurityService
{
    /**
     * Log user activity
     */
    public function logActivity(User $user, string $action, array $details = [], string $ipAddress = null)
    {
        try {
            ActivityLog::create([
                'user_id' => $user->id,
                'user_role' => $user->role,
                'action' => $action,
                'details' => json_encode($details),
                'ip_address' => $ipAddress ?? Request::ip(),
                'user_agent' => Request::header('User-Agent'),
                'session_id' => session()->getId(),
            ]);

            // Also log to Laravel's log for backup
            Log::info("User Activity: {$user->role} - {$action}", [
                'user_id' => $user->id,
                'details' => $details,
                'ip' => $ipAddress ?? Request::ip(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to log user activity: ' . $e->getMessage());
        }
    }

    /**
     * Log authentication events
     */
    public function logAuthEvent(string $event, User $user = null, array $details = [])
    {
        $details['event_type'] = 'authentication';
        $details['timestamp'] = now()->toISOString();

        if ($user) {
            $this->logActivity($user, $event, $details);
        }

        Log::info("Auth Event: $event", $details);
    }

    /**
     * Log security events (suspicious activities)
     */
    public function logSecurityEvent(string $event, array $details = [], string $severity = 'medium')
    {
        $details['event_type'] = 'security';
        $details['severity'] = $severity;
        $details['timestamp'] = now()->toISOString();

        Log::warning("Security Event: $event", $details);

        // For high severity events, also send notifications
        if ($severity === 'high') {
            $this->notifyAdminsOfSecurityEvent($event, $details);
        }
    }

    /**
     * Log compliance events (document uploads, verification status changes)
     */
    public function logComplianceEvent(string $event, array $details = [])
    {
        $details['event_type'] = 'compliance';
        $details['timestamp'] = now()->toISOString();

        Log::info("Compliance Event: $event", $details);

        // Also create a compliance record for auditing
        $this->createComplianceRecord($event, $details);
    }

    /**
     * Check for suspicious login patterns
     */
    public function checkSuspiciousLogin(User $user, string $ipAddress)
    {
        $recentLogins = ActivityLog::where('user_id', $user->id)
            ->where('action', 'login')
            ->where('created_at', '>', now()->subHours(24))
            ->count();

        if ($recentLogins > 5) {
            $this->logSecurityEvent('multiple_login_attempts', [
                'user_id' => $user->id,
                'login_count_24h' => $recentLogins,
                'ip_address' => $ipAddress,
            ], 'high');
        }

        // Check for login from different locations
        $recentIPs = ActivityLog::where('user_id', $user->id)
            ->where('action', 'login')
            ->where('created_at', '>', now()->subDays(7))
            ->distinct('ip_address')
            ->count();

        if ($recentIPs > 3) {
            $this->logSecurityEvent('multiple_ip_logins', [
                'user_id' => $user->id,
                'unique_ips_7d' => $recentIPs,
                'current_ip' => $ipAddress,
            ], 'medium');
        }
    }

    /**
     * Monitor failed login attempts
     */
    public function logFailedLogin(string $email, string $ipAddress, string $reason = 'invalid_credentials')
    {
        $this->logSecurityEvent('failed_login', [
            'email' => $email,
            'ip_address' => $ipAddress,
            'reason' => $reason,
        ], 'low');

        // Check for brute force attempts
        $recentFailures = ActivityLog::where('details->email', $email)
            ->where('action', 'failed_login')
            ->where('created_at', '>', now()->subMinutes(15))
            ->count();

        if ($recentFailures >= 5) {
            $this->logSecurityEvent('brute_force_attempt', [
                'email' => $email,
                'failure_count' => $recentFailures,
                'ip_address' => $ipAddress,
            ], 'high');
        }
    }

    /**
     * Monitor data access patterns
     */
    public function logDataAccess(User $user, string $resource, string $action, $resourceId = null)
    {
        $details = [
            'resource_type' => $resource,
            'resource_id' => $resourceId,
            'access_action' => $action,
        ];

        // Check for excessive data access
        $recentAccess = ActivityLog::where('user_id', $user->id)
            ->where('details->resource_type', $resource)
            ->where('created_at', '>', now()->subMinutes(5))
            ->count();

        if ($recentAccess > 10) {
            $this->logSecurityEvent('excessive_data_access', [
                'user_id' => $user->id,
                'resource_type' => $resource,
                'access_count' => $recentAccess,
            ], 'medium');
        }

        $this->logActivity($user, "access_{$action}_{$resource}", $details);
    }

    /**
     * Monitor charity compliance status
     */
    public function checkComplianceStatus()
    {
        // Check for charities that haven't uploaded documents in 6 months
        $staleCharities = Charity::where('verification_status', 'approved')
            ->whereDoesntHave('documents', function($q) {
                $q->where('created_at', '>', now()->subMonths(6));
            })
            ->count();

        if ($staleCharities > 0) {
            $this->logComplianceEvent('stale_documentation', [
                'charities_without_recent_docs' => $staleCharities,
            ]);
        }

        // Check for charities with pending verifications older than 30 days
        $oldPending = Charity::where('verification_status', 'pending')
            ->where('created_at', '<', now()->subDays(30))
            ->count();

        if ($oldPending > 0) {
            $this->logComplianceEvent('old_pending_verifications', [
                'pending_over_30_days' => $oldPending,
            ]);
        }

        return [
            'stale_documentation' => $staleCharities,
            'old_pending_verifications' => $oldPending,
        ];
    }

    /**
     * Generate compliance report
     */
    public function generateComplianceReport()
    {
        $report = [
            'generated_at' => now()->toISOString(),
            'total_charities' => Charity::count(),
            'approved_charities' => Charity::where('verification_status', 'approved')->count(),
            'pending_charities' => Charity::where('verification_status', 'pending')->count(),
            'rejected_charities' => Charity::where('verification_status', 'rejected')->count(),
            'charities_with_documents' => Charity::has('documents')->count(),
            'total_documents' => CharityDocument::count(),
            'document_types' => CharityDocument::selectRaw('doc_type, COUNT(*) as count')
                ->groupBy('doc_type')
                ->get()
                ->pluck('count', 'doc_type'),
        ];

        $this->logComplianceEvent('compliance_report_generated', $report);

        return $report;
    }

    private function createComplianceRecord(string $event, array $details)
    {
        try {
            // Create a compliance record (you might want to create a ComplianceRecord model)
            // For now, we'll just log it
            Log::info("Compliance Record: $event", $details);
        } catch (\Exception $e) {
            Log::error('Failed to create compliance record: ' . $e->getMessage());
        }
    }

    private function notifyAdminsOfSecurityEvent(string $event, array $details)
    {
        try {
            $admins = User::where('role', 'admin')->get();

            foreach ($admins as $admin) {
                // You could integrate with the notification service here
                Log::warning("Security Alert for Admin {$admin->id}: $event", $details);
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify admins of security event: ' . $e->getMessage());
        }
    }

    /**
     * Sanitize string input to prevent XSS attacks
     */
    public function sanitizeXSS(string $input): string
    {
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Strip dangerous HTML tags
        $input = strip_tags($input);
        
        // HTML encode special characters
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $input;
    }

    /**
     * Sanitize HTML content while preserving safe tags
     */
    public function sanitizeHTML(string $html, array $allowedTags = ['p', 'br', 'b', 'i', 'u', 'strong', 'em', 'a']): string
    {
        // Remove null bytes
        $html = str_replace(chr(0), '', $html);
        
        // Strip tags except allowed ones
        $tagString = '<' . implode('><', $allowedTags) . '>';
        $html = strip_tags($html, $tagString);
        
        // Remove javascript: and other dangerous protocols from links
        $html = preg_replace('/(<a[^>]+href=(["\']))javascript:/i', '$1#', $html);
        $html = preg_replace('/(<a[^>]+href=(["\']))data:/i', '$1#', $html);
        $html = preg_replace('/(<a[^>]+href=(["\']))vbscript:/i', '$1#', $html);
        
        // Remove event handlers
        $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        
        return $html;
    }

    /**
     * Validate and sanitize SQL-like input patterns
     */
    public function detectSQLInjection(string $input): bool
    {
        // Common SQL injection patterns
        $patterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bTRUNCATE\b.*\bTABLE\b)/i',
            '/(\bALTER\b.*\bTABLE\b)/i',
            '/(\bCREATE\b.*\b(TABLE|DATABASE)\b)/i',
            '/(\bEXEC(\s|\+)+(s|x)p\w+)/i',
            '/(;|\||<|>|%27|%3B|%3C|%3E|0x[0-9a-f]+)/i',
            '/(\bOR\b.*=.*)/i',
            '/(\bAND\b.*=.*)/i',
            '/(\'|\")(\s)*(OR|AND)(\s)*(\d+|\'|\")(\s)*=(\s)*(\d+|\'|\")/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $this->logSecurityEvent('sql_injection_attempt', [
                    'input' => substr($input, 0, 200), // Log first 200 chars
                    'pattern' => $pattern,
                    'ip_address' => Request::ip(),
                ], 'high');
                return true;
            }
        }

        return false;
    }

    /**
     * Detect XSS attack patterns
     */
    public function detectXSS(string $input): bool
    {
        // Common XSS patterns
        $patterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/<object[^>]*>.*?<\/object>/is',
            '/<embed[^>]*>.*?<\/embed>/is',
            '/on\w+\s*=\s*["\'][^"\']*["\']/i', // Event handlers
            '/javascript:/i',
            '/vbscript:/i',
            '/data:text\/html/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $this->logSecurityEvent('xss_attempt', [
                    'input' => substr($input, 0, 200),
                    'pattern' => $pattern,
                    'ip_address' => Request::ip(),
                ], 'high');
                return true;
            }
        }

        return false;
    }

    /**
     * Check if account is currently locked
     */
    public function isAccountLocked(User $user): array
    {
        if ($user->locked_until && Carbon::parse($user->locked_until)->isFuture()) {
            $remainingMinutes = now()->diffInMinutes(Carbon::parse($user->locked_until), false);
            
            return [
                'locked' => true,
                'locked_until' => $user->locked_until,
                'remaining_minutes' => abs($remainingMinutes),
                'message' => "🚫 Too many failed login attempts. Please try again after {$remainingMinutes} minutes."
            ];
        }

        return ['locked' => false];
    }

    /**
     * Record failed login attempt and lock if necessary
     */
    public function recordFailedLogin(User $user, string $ipAddress): array
    {
        // Increment failed attempts
        $user->failed_attempts = ($user->failed_attempts ?? 0) + 1;
        $remainingAttempts = 5 - $user->failed_attempts;

        // Lock account if reached limit
        if ($user->failed_attempts >= 5) {
            $user->locked_until = now()->addMinutes(10);
            $user->save();

            // Send security email
            try {
                Mail::to($user->email)->send(new TooManyLoginAttempts($user, 10, $user->failed_attempts));
            } catch (\Exception $e) {
                Log::error('Failed to send security email: ' . $e->getMessage());
            }

            // Log security event
            $this->logSecurityEvent('account_locked', [
                'user_id' => $user->id,
                'email' => $user->email,
                'failed_attempts' => $user->failed_attempts,
                'locked_until' => $user->locked_until,
                'ip_address' => $ipAddress,
            ], 'high');

            return [
                'locked' => true,
                'attempts_remaining' => 0,
                'locked_until' => $user->locked_until,
                'message' => '🚫 Too many failed login attempts. Your account has been locked for 10 minutes for security. You will receive an email with further instructions.'
            ];
        }

        $user->save();

        // Log failed attempt
        $this->logSecurityEvent('failed_login_attempt', [
            'user_id' => $user->id,
            'email' => $user->email,
            'attempts' => $user->failed_attempts,
            'remaining' => $remainingAttempts,
            'ip_address' => $ipAddress,
        ], 'medium');

        return [
            'locked' => false,
            'attempts_remaining' => $remainingAttempts,
            'message' => "⚠️ Incorrect password. You have {$remainingAttempts} attempt(s) remaining before your account is locked."
        ];
    }

    /**
     * Reset failed attempts on successful login
     */
    public function resetFailedAttempts(User $user): void
    {
        if ($user->failed_attempts > 0 || $user->locked_until) {
            $user->failed_attempts = 0;
            $user->locked_until = null;
            $user->save();

            Log::info("Reset failed attempts for user {$user->id}");
        }
    }

    /**
     * Check if user can attempt login (not locked)
     */
    public function canAttemptLogin(User $user): bool
    {
        $lockStatus = $this->isAccountLocked($user);
        return !$lockStatus['locked'];
    }

    /**
     * Get remaining lockout time in human readable format
     */
    public function getRemainingLockTime(User $user): ?string
    {
        if (!$user->locked_until) {
            return null;
        }

        $lockedUntil = Carbon::parse($user->locked_until);
        
        if ($lockedUntil->isPast()) {
            return null;
        }

        $diff = now()->diff($lockedUntil);
        
        if ($diff->i > 0) {
            return $diff->i . ' minute(s)';
        }
        
        if ($diff->s > 0) {
            return $diff->s . ' second(s)';
        }

        return 'shortly';
    }

    /**
     * Validate input against expected patterns
     */
    public function validateInput(string $input, string $type): array
    {
        $errors = [];

        switch ($type) {
            case 'reference_number':
                if (!preg_match('/^[0-9]{10,20}$/', $input)) {
                    $errors[] = 'Invalid reference number format. Must be 10-20 digits.';
                }
                break;

            case 'amount':
                if (!preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $input) || floatval($input) <= 0) {
                    $errors[] = 'Invalid amount. Must be a positive number.';
                }
                break;

            case 'email':
                if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid email format.';
                }
                break;

            case 'phone':
                if (!preg_match('/^[0-9+\-\s()]{10,15}$/', $input)) {
                    $errors[] = 'Invalid phone number format.';
                }
                break;

            case 'alphanumeric':
                if (!preg_match('/^[a-zA-Z0-9\s\-_]+$/', $input)) {
                    $errors[] = 'Only alphanumeric characters allowed.';
                }
                break;

            case 'name':
                if (!preg_match('/^[a-zA-Z\s\-\']+$/', $input)) {
                    $errors[] = 'Invalid name format.';
                }
                break;

            case 'url':
                if (!filter_var($input, FILTER_VALIDATE_URL)) {
                    $errors[] = 'Invalid URL format.';
                }
                // Check for dangerous protocols
                if (preg_match('/^(javascript|data|vbscript):/i', $input)) {
                    $errors[] = 'Dangerous URL protocol detected.';
                }
                break;
        }

        // Check for SQL injection and XSS attempts
        if ($this->detectSQLInjection($input)) {
            $errors[] = 'Potential SQL injection detected.';
        }

        if ($this->detectXSS($input)) {
            $errors[] = 'Potential XSS attack detected.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'sanitized' => $this->sanitizeXSS($input),
        ];
    }
}
