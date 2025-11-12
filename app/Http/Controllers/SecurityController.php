<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EmailChange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class SecurityController extends Controller
{
    /**
     * Change Email - Request (sends verification code)
     */
    public function changeEmailRequest(Request $request)
    {
        $user = $request->user();

        // Manual validation with better error messages
        $request->validate([
            'current_password' => 'required|string',
            'new_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'new_email_confirmation' => 'required|email|same:new_email',
        ], [
            'new_email.unique' => 'This email is already registered. Please use a different email.',
            'new_email_confirmation.same' => 'Email confirmation does not match.',
        ]);

        $newEmail = $request->input('new_email');
        $currentPassword = $request->input('current_password');

        // Verify current password
        if (!Hash::check($currentPassword, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 422);
        }

        // Check if trying to change to the same email
        if ($newEmail === $user->email) {
            return response()->json([
                'success' => false,
                'message' => 'New email must be different from current email'
            ], 422);
        }

        // Generate 6-digit verification code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Delete any old pending requests for this user
        EmailChange::where('user_id', $user->id)->delete();

        // Create new email change request
        $emailChange = EmailChange::create([
            'user_id' => $user->id,
            'old_email' => $user->email,
            'new_email' => $newEmail,
            'token' => hash('sha256', $code),
            'expires_at' => now()->addMinutes(5),
        ]);

        // Send verification code email to NEW email address
        try {
            Mail::to($newEmail)->send(
                new \App\Mail\Security\EmailChangeCodeMail($user, $code)
            );
            \Log::info('Email sent successfully to: ' . $newEmail . ' with code: ' . $code);
        } catch (\Exception $e) {
            \Log::error('Failed to send email: ' . $e->getMessage());
            // Still continue even if email fails
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent to ' . $newEmail,
            'new_email' => $newEmail,
            'code' => $code, // For testing - REMOVE IN PRODUCTION
        ]);
    }

    /**
     * Verify Email Change Code
     */
    public function verifyEmailChangeCode(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();
        $hashedCode = hash('sha256', $validated['code']);

        $emailChange = EmailChange::where('user_id', $user->id)
            ->where('token', $hashedCode)
            ->where('expires_at', '>', now())
            ->first();

        if (!$emailChange) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code'
            ], 400);
        }

        // Update user email
        $user->email = $emailChange->new_email;
        $user->save();

        // Delete the email change request
        $emailChange->delete();

        return response()->json([
            'success' => true,
            'message' => 'Email address successfully updated! Please log in with your new email.',
        ]);
    }

    /**
     * Change Email - Verify
     */
    public function verifyEmailChange(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        $hashedToken = hash('sha256', $validated['token']);

        $emailChange = EmailChange::where('token', $hashedToken)
            ->where('status', 'pending')
            ->first();

        if (!$emailChange) {
            return response()->json([
                'message' => 'Invalid or expired verification link'
            ], 404);
        }

        if ($emailChange->isExpired()) {
            $emailChange->update(['status' => 'expired']);
            return response()->json([
                'message' => 'This verification link has expired'
            ], 422);
        }

        // Update user email
        $user = $emailChange->user;
        $user->update(['email' => $emailChange->new_email]);

        // Mark email change as completed
        $emailChange->update([
            'status' => 'completed',
            'verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email address changed successfully'
        ]);
    }

    /**
     * Enable Two-Factor Authentication (Setup/Get QR Code)
     * Returns existing pending secret if available, preventing regeneration
     */
    public function enable2FA(Request $request)
    {
        $user = $request->user()->fresh();

        if ($user->two_factor_enabled) {
            return response()->json([
                'message' => 'Two-factor authentication is already enabled',
                'already_enabled' => true
            ], 422);
        }

        $google2fa = new Google2FA();
        
        // Check if user already has a pending (unverified) secret
        if ($user->two_factor_secret) {
            // Return existing secret and QR code (don't regenerate)
            try {
                $secretKey = decrypt($user->two_factor_secret);
                $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
                
                \Log::info('2FA Setup: Returning existing secret for user ' . $user->id);
            } catch (\Exception $e) {
                // If decryption fails, regenerate
                \Log::warning('2FA Setup: Failed to decrypt existing secret, regenerating', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                $secretKey = null;
            }
        }
        
        // Generate new secret only if none exists or decryption failed
        if (!isset($secretKey) || empty($secretKey)) {
            $secretKey = $google2fa->generateSecretKey();
            $recoveryCodes = $this->generateRecoveryCodes();
            
            // Store in database (encrypted)
            $user->update([
                'two_factor_secret' => encrypt($secretKey),
                'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            ]);
            
            \Log::info('2FA Setup: New secret generated for user ' . $user->id);
        }

        // Generate QR Code
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            'CharityHub',
            $user->email,
            $secretKey
        );

        // Generate QR Code SVG
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeSvg = $writer->writeString($qrCodeUrl);

        return response()->json([
            'success' => true,
            'secret' => $secretKey,
            'qr_code' => base64_encode($qrCodeSvg),
            'recovery_codes' => $recoveryCodes,
            'is_pending' => !$user->two_factor_enabled,
        ]);
    }

    /**
     * Verify and Activate 2FA
     */
    public function verify2FA(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|digits:6',
        ]);

        // Refresh user from database to get latest data
        $user = $request->user()->fresh();

        if ($user->two_factor_enabled) {
            return response()->json([
                'message' => 'Two-factor authentication is already enabled'
            ], 422);
        }

        if (!$user->two_factor_secret) {
            return response()->json([
                'message' => 'Please start setup first by clicking Enable 2FA'
            ], 422);
        }

        // Verify code
        $google2fa = new Google2FA();
        
        try {
            $secret = decrypt($user->two_factor_secret);
        } catch (\Exception $e) {
            \Log::error('Failed to decrypt 2FA secret: ' . $e->getMessage());
            return response()->json([
                'message' => 'Invalid 2FA configuration. Please start setup again'
            ], 422);
        }

        $valid = $google2fa->verifyKey($secret, $validated['code'], 2); // Allow 2 windows (60 seconds)

        if (!$valid) {
            \Log::warning('2FA Verification failed for user ' . $user->id, [
                'code_length' => strlen($validated['code'])
            ]);
            
            return response()->json([
                'message' => 'Invalid 2FA code, please try again',
                'hint' => 'Make sure you are entering the latest code from your authenticator app'
            ], 422);
        }

        // Activate 2FA
        $user->update([
            'two_factor_enabled' => true,
            'two_factor_enabled_at' => now(),
        ]);

        \Log::info('2FA successfully enabled for user ' . $user->id);

        // Get recovery codes
        try {
            $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        } catch (\Exception $e) {
            \Log::error('Failed to decrypt recovery codes: ' . $e->getMessage());
            $recoveryCodes = [];
        }

        // Send confirmation email
        try {
            Mail::to($user->email)->queue(
                new \App\Mail\Security\TwoFactorEnabledMail($user, $recoveryCodes)
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send 2FA enabled email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication enabled successfully!',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Disable Two-Factor Authentication
     */
    public function disable2FA(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        // Verify password
        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Password is incorrect'
            ], 422);
        }

        if (!$user->two_factor_enabled) {
            return response()->json([
                'message' => '2FA is not enabled'
            ], 422);
        }

        // Disable 2FA
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => '2FA disabled successfully'
        ]);
    }

    /**
     * Get 2FA Status
     */
    public function get2FAStatus(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'enabled' => $user->two_factor_enabled,
            'enabled_at' => $user->two_factor_enabled_at?->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Generate Recovery Codes
     */
    private function generateRecoveryCodes($count = 10)
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::upper(Str::random(4) . '-' . Str::random(4));
        }
        return $codes;
    }
}
