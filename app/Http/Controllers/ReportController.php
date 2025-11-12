<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\AdminActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    /**
     * Submit a new report (Donor or Charity)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target_type' => 'required|in:user,charity,campaign,donation',
            'target_id' => 'required|integer',
            'report_type' => 'required|in:fraud,misleading_information,abuse,spam,fake_donation,misuse_of_funds,inappropriate_content,other',
            'severity' => 'nullable|in:low,medium,high', // Optional - admin will decide
            'details' => 'required|string|min:10|max:1000',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
            // Legacy support
            'reported_entity_type' => 'nullable|in:user,charity,campaign,donation',
            'reported_entity_id' => 'nullable|integer',
            'reason' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $evidencePath = null;

        // Handle evidence file upload
        if ($request->hasFile('evidence')) {
            $evidencePath = $request->file('evidence')->store('reports/evidence', 'public');
        }

        $report = Report::create([
            'reporter_id' => $user->id,
            'reporter_role' => $user->role,
            'target_type' => $request->target_type,
            'target_id' => $request->target_id,
            'report_type' => $request->report_type,
            'severity' => $request->severity ?? 'pending', // Default to 'pending' - admin will decide
            'details' => $request->details,
            // Legacy fields
            'reported_entity_type' => $request->target_type,
            'reported_entity_id' => $request->target_id,
            'reason' => $request->report_type,
            'description' => $request->details,
            'evidence_path' => $evidencePath,
            'status' => 'pending',
        ]);

        // Notify admins
        \App\Services\NotificationHelper::newReportSubmitted($report);

        return response()->json([
            'message' => 'Report submitted successfully. Our team will review it shortly.',
            'report' => $report->load('reporter'),
        ], 201);
    }

    /**
     * Get current user's submitted reports
     */
    public function myReports(Request $request)
    {
        $user = $request->user();
        
        $reports = Report::where('reporter_id', $user->id)
            ->with(['reviewer'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($reports);
    }

    /**
     * Get all reports (Admin only)
     */
    public function index(Request $request)
    {
        $query = Report::with(['reporter', 'reviewer']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by entity type
        if ($request->has('entity_type')) {
            $query->where('reported_entity_type', $request->entity_type);
        }

        // Filter by reason
        if ($request->has('reason')) {
            $query->where('reason', $request->reason);
        }

        // Search by description
        if ($request->has('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        // Order by most recent first
        $query->orderBy('created_at', 'desc');

        $reports = $query->paginate(20);
        
        // Load reported entity/user info for each report
        $reports->getCollection()->transform(function ($report) {
            $entityData = null;
            
            switch ($report->reported_entity_type) {
                case 'user':
                    $entityData = \App\Models\User::select('id', 'name', 'email', 'profile_image', 'role')
                        ->find($report->reported_entity_id);
                    // Add full URL for profile image
                    if ($entityData && $entityData->profile_image) {
                        $entityData->profile_picture_url = url('storage/' . $entityData->profile_image);
                    }
                    break;
                case 'charity':
                    $entityData = \App\Models\Charity::with('owner:id,name,email,profile_image')
                        ->select('id', 'name', 'contact_email', 'logo_path', 'owner_id')
                        ->find($report->reported_entity_id);
                    // Add full URL for charity logo
                    if ($entityData && $entityData->logo_path) {
                        $entityData->logo_url = url('storage/' . $entityData->logo_path);
                    }
                    break;
                case 'campaign':
                    $entityData = \App\Models\Campaign::with('charity:id,name,logo_path')
                        ->select('id', 'title', 'charity_id')
                        ->find($report->reported_entity_id);
                    break;
                case 'donation':
                    $entityData = \App\Models\Donation::with(['donor:id,name,profile_image', 'charity:id,name,logo_path'])
                        ->select('id', 'donor_id', 'charity_id', 'amount')
                        ->find($report->reported_entity_id);
                    break;
            }
            
            $report->reported_entity = $entityData;
            
            // Add reporter profile picture URL
            if ($report->reporter && $report->reporter->profile_image) {
                $report->reporter->profile_picture_url = url('storage/' . $report->reporter->profile_image);
            }
            
            // If reporter is charity admin, load their charity logo too
            if ($report->reporter && $report->reporter->role === 'charity_admin') {
                $charity = \App\Models\Charity::where('owner_id', $report->reporter->id)
                    ->select('id', 'name', 'logo_path')
                    ->first();
                if ($charity) {
                    $report->reporter->charity_info = $charity;
                    if ($charity->logo_path) {
                        $report->reporter->charity_logo_url = url('storage/' . $charity->logo_path);
                    }
                }
            }
            
            return $report;
        });

        return response()->json($reports);
    }

    /**
     * Get single report details (Admin only)
     */
    public function show(Report $report)
    {
        $report->load(['reporter', 'reviewer']);

        // Load the reported entity based on type
        $entityData = null;
        switch ($report->reported_entity_type) {
            case 'user':
                $entityData = \App\Models\User::find($report->reported_entity_id);
                // Add profile picture URL
                if ($entityData && $entityData->profile_image) {
                    $entityData->profile_picture_url = url('storage/' . $entityData->profile_image);
                }
                break;
            case 'charity':
                $entityData = \App\Models\Charity::with('owner')->find($report->reported_entity_id);
                // Add logo URL
                if ($entityData && $entityData->logo_path) {
                    $entityData->logo_url = url('storage/' . $entityData->logo_path);
                }
                break;
            case 'campaign':
                $entityData = \App\Models\Campaign::with('charity')->find($report->reported_entity_id);
                break;
            case 'donation':
                $entityData = \App\Models\Donation::with(['donor', 'charity'])->find($report->reported_entity_id);
                break;
        }

        // Add reporter profile picture URL
        if ($report->reporter && $report->reporter->profile_image) {
            $report->reporter->profile_picture_url = url('storage/' . $report->reporter->profile_image);
        }
        
        // If reporter is charity admin, load their charity info
        if ($report->reporter && $report->reporter->role === 'charity_admin') {
            $charity = \App\Models\Charity::where('owner_id', $report->reporter->id)
                ->select('id', 'name', 'logo_path')
                ->first();
            if ($charity) {
                $report->reporter->charity_info = $charity;
                if ($charity->logo_path) {
                    $report->reporter->charity_logo_url = url('storage/' . $charity->logo_path);
                }
            }
        }

        return response()->json([
            'report' => $report,
            'reported_entity' => $entityData,
        ]);
    }

    /**
     * Review and take action on a report (Admin only)
     */
    public function review(Request $request, Report $report)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:under_review,resolved,dismissed',
            'action_taken' => 'nullable|in:none,warned,suspended,deleted,contacted',
            'severity' => 'nullable|in:low,medium,high,critical', // Admin determines severity
            'penalty_days' => 'nullable|integer|min:1|max:365', // For suspensions
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = $request->user();

        $updateData = [
            'status' => $request->status,
            'action_taken' => $request->action_taken ?? 'none',
            'admin_notes' => $request->admin_notes,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ];
        
        // Admin can set/update severity
        if ($request->has('severity')) {
            $updateData['severity'] = $request->severity;
        }
        
        $report->update($updateData);
        
        // Handle user suspension if action_taken is 'suspended'
        if ($request->action_taken === 'suspended' && $request->has('penalty_days')) {
            $this->suspendUser($report->target_id, $request->penalty_days, $admin->id, $report->id);
        }

        // Log admin action
        AdminActionLog::logAction(
            $admin->id,
            'review_report',
            'Report',
            $report->id,
            [
                'report_reason' => $report->reason,
                'reported_entity' => $report->reported_entity_type . ' #' . $report->reported_entity_id,
                'action_taken' => $request->action_taken,
                'status' => $request->status,
            ],
            $request->admin_notes
        );

        return response()->json([
            'message' => 'Report reviewed successfully',
            'report' => $report->load(['reporter', 'reviewer']),
        ]);
    }

    /**
     * Delete a report (Admin only)
     */
    public function destroy(Report $report)
    {
        $admin = request()->user();

        // Log deletion
        AdminActionLog::logAction(
            $admin->id,
            'delete_report',
            'Report',
            $report->id,
            [
                'reason' => $report->reason,
                'reporter' => $report->reporter->name,
            ]
        );

        // Delete evidence file if exists
        if ($report->evidence_path) {
            Storage::disk('public')->delete($report->evidence_path);
        }

        $report->delete();

        return response()->json([
            'message' => 'Report deleted successfully',
        ]);
    }

    /**
     * Get report statistics (Admin only)
     */
    public function statistics()
    {
        return response()->json([
            'total' => Report::count(),
            'pending' => Report::where('status', 'pending')->count(),
            'under_review' => Report::where('status', 'under_review')->count(),
            'resolved' => Report::where('status', 'resolved')->count(),
            'dismissed' => Report::where('status', 'dismissed')->count(),
            'by_reason' => Report::selectRaw('reason, COUNT(*) as count')
                ->groupBy('reason')
                ->get(),
            'recent' => Report::with('reporter')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ]);
    }
    
    /**
     * Suspend a user based on report action
     */
    private function suspendUser($userId, $penaltyDays, $adminId, $reportId)
    {
        $user = \App\Models\User::find($userId);
        
        if (!$user) {
            return false;
        }
        
        // Update user status
        $user->update([
            'status' => 'suspended',
            'suspended_until' => now()->addDays($penaltyDays),
        ]);
        
        // Log the suspension
        AdminActionLog::logAction(
            $adminId,
            'suspend_user',
            'User',
            $userId,
            [
                'reason' => 'Report #' . $reportId,
                'penalty_days' => $penaltyDays,
                'suspended_until' => now()->addDays($penaltyDays)->format('Y-m-d H:i:s'),
            ],
            "User suspended for {$penaltyDays} days due to report violation"
        );
        
        // Send notification to user
        try {
            \App\Services\NotificationHelper::userSuspended($user, $penaltyDays);
        } catch (\Exception $e) {
            \Log::error('Failed to send suspension notification: ' . $e->getMessage());
        }
        
        return true;
    }
}
