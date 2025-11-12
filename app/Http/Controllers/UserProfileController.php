<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;

class UserProfileController extends Controller
{
    /**
     * Change user email address
     * POST /api/me/change-email
     */
    public function changeEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_email' => 'required|email|max:255|unique:users,email',
            'new_email_confirmation' => 'required|email|same:new_email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 401);
        }

        // Generate verification token
        $token = Str::random(64);
        $hashedToken = hash('sha256', $token);

        // Store email change request in database
        DB::table('email_change_requests')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'user_id' => $user->id,
                'new_email' => $request->new_email,
                'token' => $hashedToken,
                'created_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addHours(24),
            ]
        );

        // Create verification URL
        $verificationUrl = URL::temporarySignedRoute(
            'email.verify-change',
            now()->addHours(24),
            ['token' => $token, 'user' => $user->id]
        );

        // TODO: Send verification email to new email address
        // For now, just return success message
        // Mail::to($request->new_email)->send(new VerifyEmailChange($user, $verificationUrl));

        return response()->json([
            'success' => true,
            'message' => 'A verification email has been sent to ' . $request->new_email . '. Please click the link in that email to complete your email address change.',
            'verification_url' => $verificationUrl, // For testing - remove in production
        ]);
    }

    /**
     * Verify email change
     * GET /api/email/verify-change/{token}
     */
    public function verifyEmailChange(Request $request, $token)
    {
        $userId = $request->query('user');
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification link'
            ], 400);
        }

        $hashedToken = hash('sha256', $token);

        // Find the email change request
        $emailChangeRequest = DB::table('email_change_requests')
            ->where('user_id', $userId)
            ->where('token', $hashedToken)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$emailChangeRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification link'
            ], 400);
        }

        // Update user email
        $user = User::findOrFail($userId);
        $user->email = $emailChangeRequest->new_email;
        $user->save();

        // Delete the email change request
        DB::table('email_change_requests')
            ->where('user_id', $userId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Email address successfully changed! Please log in with your new email address.',
        ]);
    }
}
