<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class EmailService
{
    /**
     * Send a test email to verify email configuration
     */
    public function sendTestEmail(string $toEmail, string $toName = 'Test User'): array
    {
        try {
            Mail::send('emails.test-email', ['name' => $toName], function ($message) use ($toEmail, $toName) {
                $message->to($toEmail, $toName)
                    ->subject('CharityConnect - Test Email');
            });

            Log::info('Test email sent successfully', ['to' => $toEmail]);

            return [
                'success' => true,
                'message' => 'Test email sent successfully',
                'recipient' => $toEmail
            ];
        } catch (Exception $e) {
            Log::error('Failed to send test email', [
                'to' => $toEmail,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send verification email for new charity registration
     */
    public function sendVerificationEmail(User $user, string $verificationToken): array
    {
        try {
            $verificationUrl = config('app.frontend_url') . '/verify-email?token=' . $verificationToken;

            Mail::send('emails.verification', [
                'name' => $user->name,
                'verificationUrl' => $verificationUrl,
                'token' => $verificationToken
            ], function ($message) use ($user) {
                $message->to($user->email, $user->name)
                    ->subject('Verify Your CharityConnect Account');
            });

            Log::info('Verification email sent', ['user_id' => $user->id, 'email' => $user->email]);

            return ['success' => true, 'message' => 'Verification email sent'];
        } catch (Exception $e) {
            Log::error('Failed to send verification email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'message' => 'Failed to send verification email'];
        }
    }

    /**
     * Send donation confirmation email
     */
    public function sendDonationConfirmationEmail($donation): array
    {
        try {
            $donor = $donation->user;
            $campaign = $donation->campaign;

            Mail::send('emails.donation-confirmation', [
                'donorName' => $donor->name,
                'campaignName' => $campaign->title,
                'amount' => number_format($donation->amount, 2),
                'referenceNumber' => $donation->reference_number,
                'donationDate' => $donation->created_at->format('F d, Y h:i A'),
                'receiptUrl' => $donation->receipt_path ? asset('storage/' . $donation->receipt_path) : null,
            ], function ($message) use ($donor) {
                $message->to($donor->email, $donor->name)
                    ->subject('Donation Confirmation - CharityConnect');
            });

            Log::info('Donation confirmation email sent', [
                'donation_id' => $donation->id,
                'donor_id' => $donor->id
            ]);

            return ['success' => true, 'message' => 'Donation confirmation sent'];
        } catch (Exception $e) {
            Log::error('Failed to send donation confirmation', [
                'donation_id' => $donation->id ?? null,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'message' => 'Failed to send confirmation'];
        }
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(User $user, string $resetToken): array
    {
        try {
            $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $resetToken;

            Mail::send('emails.password-reset', [
                'name' => $user->name,
                'resetUrl' => $resetUrl,
                'expiresIn' => 60 // minutes
            ], function ($message) use ($user) {
                $message->to($user->email, $user->name)
                    ->subject('Reset Your Password - CharityConnect');
            });

            Log::info('Password reset email sent', ['user_id' => $user->id]);

            return ['success' => true, 'message' => 'Password reset email sent'];
        } catch (Exception $e) {
            Log::error('Failed to send password reset email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'message' => 'Failed to send reset email'];
        }
    }

    /**
     * Send charity verification status update
     */
    public function sendCharityVerificationStatusEmail(User $user, string $status, ?string $rejectionReason = null): array
    {
        try {
            Mail::send('emails.charity-verification-status', [
                'name' => $user->name,
                'status' => $status,
                'rejectionReason' => $rejectionReason,
                'dashboardUrl' => config('app.frontend_url') . '/charity/dashboard'
            ], function ($message) use ($user, $status) {
                $subject = $status === 'approved' 
                    ? 'Your Charity Has Been Verified!' 
                    : 'Charity Verification Update';
                    
                $message->to($user->email, $user->name)
                    ->subject($subject);
            });

            Log::info('Charity verification status email sent', [
                'user_id' => $user->id,
                'status' => $status
            ]);

            return ['success' => true, 'message' => 'Verification status email sent'];
        } catch (Exception $e) {
            Log::error('Failed to send verification status email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'message' => 'Failed to send status email'];
        }
    }

    /**
     * Send campaign approval notification
     */
    public function sendCampaignApprovalEmail($campaign, string $status): array
    {
        try {
            $charity = $campaign->charity;
            $charityAdmin = $charity->user;

            Mail::send('emails.campaign-approval', [
                'charityName' => $charity->organization_name,
                'campaignName' => $campaign->title,
                'status' => $status,
                'campaignUrl' => config('app.frontend_url') . '/campaigns/' . $campaign->id
            ], function ($message) use ($charityAdmin, $status) {
                $subject = $status === 'approved' 
                    ? 'Campaign Approved!' 
                    : 'Campaign Status Update';
                    
                $message->to($charityAdmin->email, $charityAdmin->name)
                    ->subject($subject);
            });

            Log::info('Campaign approval email sent', [
                'campaign_id' => $campaign->id,
                'status' => $status
            ]);

            return ['success' => true, 'message' => 'Campaign approval email sent'];
        } catch (Exception $e) {
            Log::error('Failed to send campaign approval email', [
                'campaign_id' => $campaign->id ?? null,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'message' => 'Failed to send approval email'];
        }
    }

    /**
     * Send welcome email for new users
     */
    public function sendWelcomeEmail(User $user): array
    {
        try {
            $dashboardUrl = $user->role === 'donor' 
                ? config('app.frontend_url') . '/donor/dashboard'
                : config('app.frontend_url') . '/charity/dashboard';

            Mail::send('emails.welcome', [
                'name' => $user->name,
                'role' => $user->role,
                'dashboardUrl' => $dashboardUrl
            ], function ($message) use ($user) {
                $message->to($user->email, $user->name)
                    ->subject('Welcome to CharityConnect!');
            });

            Log::info('Welcome email sent', ['user_id' => $user->id]);

            return ['success' => true, 'message' => 'Welcome email sent'];
        } catch (Exception $e) {
            Log::error('Failed to send welcome email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'message' => 'Failed to send welcome email'];
        }
    }

    /**
     * Test SMTP connection
     */
    public function testConnection(): array
    {
        try {
            // Check if SMTP is configured
            $mailer = config('mail.default');
            $host = config('mail.mailers.smtp.host');
            $port = config('mail.mailers.smtp.port');
            
            // Try to send a test email to verify configuration
            Mail::raw('SMTP connection test from CharityConnect', function ($message) {
                $message->to(config('mail.from.address'))
                    ->subject('SMTP Connection Test');
            });

            Log::info('SMTP connection test successful');

            return [
                'success' => true,
                'message' => 'SMTP connection successful',
                'mailer' => $mailer,
                'host' => $host,
                'port' => $port
            ];
        } catch (Exception $e) {
            Log::error('SMTP connection test failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'SMTP connection failed',
                'error' => $e->getMessage(),
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port')
            ];
        }
    }

    /**
     * Log email sending for debugging
     */
    protected function logEmail(string $type, string $recipient, bool $success, ?string $error = null): void
    {
        Log::channel('mail')->info('Email sent', [
            'type' => $type,
            'recipient' => $recipient,
            'success' => $success,
            'error' => $error,
            'timestamp' => now()->toDateTimeString()
        ]);
    }
}
