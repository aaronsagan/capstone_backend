<?php

namespace App\Http\Controllers;

use App\Mail\{
    VerifyEmailMail,
    ResendVerificationMail,
    DonorReactivationMail,
    CharityReactivationMail,
    ChangeEmailMail,
    TwoFactorSetupMail,
    AccountStatusMail,
    PasswordResetMail
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Charity;

class AuthEmailController extends Controller
{
    /**
     * Send verification email after registration
     * POST /api/email/send-verification
     */
    public function sendVerification(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $user = User::findOrFail($request->user_id);

            // Check if already verified
            if ($user->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already verified'
                ], 400);
            }

            // Delete any existing tokens for this email
            DB::table('email_verifications')->where('email', $user->email)->delete();

            // Create new verification token
            $token = Str::random(60);
            DB::table('email_verifications')->insert([
                'email' => $user->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);

            // Send email
            Mail::to($user->email)->send(new VerifyEmailMail($user, $token));

            Log::info('Verification email sent', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Verification email sent successfully',
                'email' => $user->email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send verification email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify email using token
     * GET /auth/verify-email?token=xxx
     */
    public function verifyEmail(Request $request)
    {
        try {
            $token = $request->query('token');

            if (!$token) {
                return redirect(config('app.frontend_url') . '/auth/verification-failed?reason=missing_token');
            }

            // Find verification record
            $record = DB::table('email_verifications')
                ->where('token', $token)
                ->first();

            if (!$record) {
                return redirect(config('app.frontend_url') . '/auth/verification-failed?reason=invalid_token');
            }

            // Check if token expired (24 hours)
            $created = Carbon::parse($record->created_at);
            if ($created->diffInHours(now()) > 24) {
                DB::table('email_verifications')->where('token', $token)->delete();
                return redirect(config('app.frontend_url') . '/auth/verification-failed?reason=expired');
            }

            // Find and verify user
            $user = User::where('email', $record->email)->first();

            if (!$user) {
                return redirect(config('app.frontend_url') . '/auth/verification-failed?reason=user_not_found');
            }

            // Mark email as verified
            $user->email_verified_at = Carbon::now();
            $user->save();

            // Delete the verification token
            DB::table('email_verifications')->where('token', $token)->delete();

            Log::info('Email verified successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return redirect(config('app.frontend_url') . '/auth/verification-success?email=' . urlencode($user->email));
        } catch (\Exception $e) {
            Log::error('Email verification failed', [
                'error' => $e->getMessage()
            ]);

            return redirect(config('app.frontend_url') . '/auth/verification-failed?reason=error');
        }
    }

    /**
     * Resend verification email
     * POST /api/email/resend-verification
     */
    public function resendVerification(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $user = User::where('email', $request->email)->firstOrFail();

            // Check if already verified
            if ($user->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already verified'
                ], 400);
            }

            // Delete old tokens
            DB::table('email_verifications')->where('email', $user->email)->delete();

            // Create new token
            $token = Str::random(60);
            DB::table('email_verifications')->insert([
                'email' => $user->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);

            // Send email
            Mail::to($user->email)->send(new ResendVerificationMail($user, $token));

            Log::info('Verification email resent', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Verification email resent successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to resend verification email', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resend verification email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send password reset email
     * POST /api/email/password-reset
     */
    public function sendPasswordReset(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $user = User::where('email', $request->email)->firstOrFail();

            // Delete old reset tokens
            DB::table('password_resets')->where('email', $user->email)->delete();

            // Create new token
            $token = Str::random(60);
            DB::table('password_resets')->insert([
                'email' => $user->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);

            // Send email
            Mail::to($user->email)->send(new PasswordResetMail($user, $token));

            Log::info('Password reset email sent', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset password using token
     * POST /api/auth/reset-password
     */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
                'email' => 'required|email',
                'password' => 'required|string|min:8|confirmed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Find reset record
            $record = DB::table('password_resets')
                ->where('email', $request->email)
                ->where('token', $request->token)
                ->first();

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired reset token'
                ], 400);
            }

            // Check if token expired (60 minutes)
            $created = Carbon::parse($record->created_at);
            if ($created->diffInMinutes(now()) > 60) {
                DB::table('password_resets')->where('token', $request->token)->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Reset token has expired'
                ], 400);
            }

            // Update user password
            $user = User::where('email', $request->email)->firstOrFail();
            $user->password = bcrypt($request->password);
            $user->save();

            // Delete the reset token
            DB::table('password_resets')->where('token', $request->token)->delete();

            Log::info('Password reset successful', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Password reset failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send donor reactivation email
     * POST /api/email/donor-reactivation
     */
    public function sendDonorReactivation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $user = User::findOrFail($request->user_id);

            $token = Str::random(60);

            // Send email
            Mail::to($user->email)->send(new DonorReactivationMail($user, $token));

            Log::info('Donor reactivation email sent', [
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reactivation email sent successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send donor reactivation email', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send reactivation email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send charity reactivation email
     * POST /api/email/charity-reactivation
     */
    public function sendCharityReactivation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $user = User::findOrFail($request->user_id);
            $charity = $user->charity;

            $token = Str::random(60);

            // Send email
            Mail::to($user->email)->send(new CharityReactivationMail($user, $charity, $token));

            Log::info('Charity reactivation email sent', [
                'user_id' => $user->id,
                'charity_id' => $charity->id ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Charity reactivation email sent successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send charity reactivation email', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send reactivation email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send email change confirmation
     * POST /api/email/change-email
     */
    public function sendChangeEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'new_email' => 'required|email|unique:users,email'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $user = User::findOrFail($request->user_id);
            $newEmail = $request->new_email;

            // Delete old email change requests
            DB::table('email_changes')->where('user_id', $user->id)->delete();

            // Create new token
            $token = Str::random(60);
            DB::table('email_changes')->insert([
                'user_id' => $user->id,
                'old_email' => $user->email,
                'new_email' => $newEmail,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);

            // Send email to NEW email address
            Mail::to($newEmail)->send(new ChangeEmailMail($user, $newEmail, $token));

            Log::info('Email change confirmation sent', [
                'user_id' => $user->id,
                'old_email' => $user->email,
                'new_email' => $newEmail
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Confirmation email sent to new address'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send email change confirmation', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send confirmation email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm email change
     * GET /auth/confirm-email-change?token=xxx
     */
    public function confirmEmailChange(Request $request)
    {
        try {
            $token = $request->query('token');

            if (!$token) {
                return redirect(config('app.frontend_url') . '/auth/email-change-failed?reason=missing_token');
            }

            // Find email change record
            $record = DB::table('email_changes')
                ->where('token', $token)
                ->first();

            if (!$record) {
                return redirect(config('app.frontend_url') . '/auth/email-change-failed?reason=invalid_token');
            }

            // Check if token expired (60 minutes)
            $created = Carbon::parse($record->created_at);
            if ($created->diffInMinutes(now()) > 60) {
                DB::table('email_changes')->where('token', $token)->delete();
                return redirect(config('app.frontend_url') . '/auth/email-change-failed?reason=expired');
            }

            // Update user email
            $user = User::findOrFail($record->user_id);
            $user->email = $record->new_email;
            $user->save();

            // Delete the email change token
            DB::table('email_changes')->where('token', $token)->delete();

            Log::info('Email change confirmed', [
                'user_id' => $user->id,
                'new_email' => $user->email
            ]);

            return redirect(config('app.frontend_url') . '/auth/email-change-success');
        } catch (\Exception $e) {
            Log::error('Email change confirmation failed', [
                'error' => $e->getMessage()
            ]);

            return redirect(config('app.frontend_url') . '/auth/email-change-failed?reason=error');
        }
    }

    /**
     * Send 2FA setup confirmation with backup codes
     * POST /api/email/2fa-setup
     */
    public function send2FASetup(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'backup_codes' => 'required|array|min:8'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $user = User::findOrFail($request->user_id);
            $backupCodes = $request->backup_codes;

            // Store backup codes in database
            DB::table('two_factor_codes')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'backup_codes' => json_encode($backupCodes),
                    'is_enabled' => true,
                    'updated_at' => Carbon::now()
                ]
            );

            // Send email
            Mail::to($user->email)->send(new TwoFactorSetupMail($user, $backupCodes));

            Log::info('2FA setup email sent', [
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => '2FA setup email sent successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send 2FA setup email', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send 2FA setup email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send account status change notification
     * POST /api/email/account-status
     */
    public function sendAccountStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'status' => 'required|in:deactivated,reactivated,suspended',
                'reason' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $user = User::findOrFail($request->user_id);

            // Send email
            Mail::to($user->email)->send(new AccountStatusMail($user, $request->status, $request->reason));

            Log::info('Account status email sent', [
                'user_id' => $user->id,
                'status' => $request->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Account status notification sent successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send account status email', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send account status notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
