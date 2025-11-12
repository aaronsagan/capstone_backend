<?php

namespace App\Http\Controllers;

use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class EmailController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Test SMTP connection
     * GET /api/email/test-connection
     */
    public function testConnection()
    {
        try {
            $result = $this->emailService->testConnection();
            
            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('SMTP connection test failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send test email
     * POST /api/test-email
     * Body: { "email": "test@example.com", "name": "Test User" }
     */
    public function sendTestEmail(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $email = $request->input('email');
        $name = $request->input('name', 'Test User');

        try {
            $result = $this->emailService->sendTestEmail($email, $name);
            
            // Log the attempt
            Log::info('Test email requested', [
                'email' => $email,
                'success' => $result['success'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('Test email sending failed', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get email sending statistics (Admin only)
     * GET /api/admin/email/stats
     */
    public function getStats()
    {
        try {
            // Read log file to get stats
            $logPath = storage_path('logs/laravel.log');
            $stats = [
                'total_sent' => 0,
                'total_failed' => 0,
                'recent_emails' => []
            ];

            if (file_exists($logPath)) {
                $logContent = file_get_contents($logPath);
                $stats['total_sent'] = substr_count($logContent, 'email sent successfully');
                $stats['total_failed'] = substr_count($logContent, 'Failed to send');
            }

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send verification email
     * POST /api/email/send-verification
     */
    public function sendVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $user = \App\Models\User::findOrFail($request->user_id);
            $result = $this->emailService->sendVerificationEmail($user, $request->token);
            
            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('Verification email sending failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send password reset email
     * POST /api/email/send-password-reset
     */
    public function sendPasswordReset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $user = \App\Models\User::findOrFail($request->user_id);
            $result = $this->emailService->sendPasswordResetEmail($user, $request->token);
            
            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('Password reset email sending failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset email',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
