<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;

class SuspensionController extends Controller
{
    // Map severity to penalty days
    private function getSeverityPenaltyDays($severity)
    {
        return match($severity) {
            'low' => 3,
            'medium' => 7,
            'high' => 15,
            default => 7,
        };
    }

    public function approveReport(Request $request, $reportId)
    {
        $request->validate([
            'penalty_days' => 'nullable|integer|min:1|max:90',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $report = Report::findOrFail($reportId);
        $admin = $request->user();
        $penaltyDays = $request->penalty_days ?? $this->getSeverityPenaltyDays($report->severity);
        $targetUser = $report->getTargetUser();

        if (!$targetUser) {
            return response()->json(['message' => 'Target user not found'], 404);
        }

        $suspendedUntil = now()->addDays($penaltyDays);
        $targetUser->update([
            'status' => 'suspended',
            'suspended_until' => $suspendedUntil,
            'suspension_reason' => $report->details,
            'suspension_level' => $report->severity,
        ]);

        $report->update([
            'status' => 'approved',
            'penalty_days' => $penaltyDays,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'admin_notes' => $request->admin_notes,
            'action_taken' => 'suspended',
        ]);

        // Send in-app notification
        \App\Services\NotificationHelper::accountSuspended($targetUser, $report->details, $suspendedUntil, $penaltyDays);

        // Send detailed email notification
        try {
            $now = now();
            $diff = $now->diff($suspendedUntil);
            $daysRemaining = $diff->days;
            $hoursRemaining = $diff->h;
            
            $fromAddress = config('mail.from.address');
            $fromName = config('mail.from.name');
            
            \Mail::send('emails.account-suspended', [
                'user_name' => $targetUser->name,
                'reason' => $report->details,
                'severity' => $report->severity,
                'penalty_days' => $penaltyDays,
                'suspended_on' => $now->format('M d, Y h:i A'),
                'suspended_until' => $suspendedUntil->format('M d, Y h:i A'),
                'days_remaining' => $daysRemaining,
                'hours_remaining' => $hoursRemaining,
            ], function($mail) use ($targetUser, $fromAddress, $fromName) {
                if ($fromAddress) $mail->from($fromAddress, $fromName ?: config('app.name'));
                $mail->to($targetUser->email)->subject('⚠️ Account Suspended - Action Required');
            });
            
            \Log::info('Suspension email sent successfully', [
                'user_id' => $targetUser->id,
                'email' => $targetUser->email,
                'penalty_days' => $penaltyDays
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to send suspension email', [
                'error' => $e->getMessage(),
                'user_id' => $targetUser->id,
                'trace' => $e->getTraceAsString()
            ]);
        }

        return response()->json(['message' => 'User suspended', 'suspended_until' => $suspendedUntil]);
    }

    public function rejectReport(Request $request, $reportId)
    {
        $request->validate(['admin_notes' => 'required|string|max:1000']);
        $report = Report::findOrFail($reportId);
        
        $report->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'admin_notes' => $request->admin_notes,
        ]);

        return response()->json(['message' => 'Report rejected']);
    }
}
