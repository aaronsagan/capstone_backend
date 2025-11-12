<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserActivityLogController extends Controller
{
    /**
     * Get all user activity logs with filters
     * Only returns logs where the user and related records actually exist
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('user:id,name,email,role')
            ->whereHas('user') // Only get logs where user still exists
            ->orderBy('created_at', 'desc');

        // Filter by action type (map from action_type to action)
        if ($request->has('action_type') && $request->action_type !== 'all') {
            $query->where('action', $request->action_type);
        }

        // Filter by user role (donor or charity_admin or admin)
        if ($request->has('target_type') && $request->target_type !== 'all') {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('role', $request->target_type);
            });
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Search by user name or email
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(50);
        
        // Transform the data to match frontend expectations
        $logs->getCollection()->transform(function ($log) {
            return [
                'id' => $log->id,
                'user' => [
                    'id' => $log->user->id ?? null,
                    'name' => $log->user->name ?? 'Unknown',
                    'email' => $log->user->email ?? 'N/A',
                    'role' => $log->user->role ?? $log->user_role ?? 'N/A',
                ],
                'action_type' => $log->action,
                'description' => $this->generateDescription($log),
                'target_type' => $log->details['resource_type'] ?? null,
                'target_id' => $log->details['resource_id'] ?? null,
                'details' => $log->details,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at,
            ];
        });

        return response()->json($logs);
    }

    /**
     * Get activity statistics
     * Only counts logs where users and related records actually exist
     */
    public function statistics()
    {
        try {
            // Only count logs with existing users
            $baseQuery = ActivityLog::whereHas('user');
        
        // Get unique actions from valid logs only
        $uniqueActions = $baseQuery->distinct()->pluck('action')->toArray();
        
        $stats = [
            'total' => ActivityLog::whereHas('user')->count(),
            // Count actual donations from donations table, not activity logs
            'donations' => \App\Models\Donation::count(),
            // Count actual campaigns from campaigns table
            'campaigns' => \App\Models\Campaign::count(),
            // Count actual user registrations from users table
            'registrations' => \App\Models\User::whereIn('role', ['donor', 'charity_admin'])->count(),
            'logins_today' => ActivityLog::whereHas('user')
                ->where('action', 'login')
                ->whereDate('created_at', today())
                ->count(),
            'unique_actions' => $uniqueActions,
            'by_action' => ActivityLog::whereHas('user')
                ->select('action', DB::raw('count(*) as count'))
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->get()
                ->map(function($item) {
                    return [
                        'action_type' => $item->action,
                        'count' => $item->count
                    ];
                }),
            'recent_activities' => ActivityLog::with('user:id,name,email,role')
                ->whereHas('user')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($log) {
                    return [
                        'id' => $log->id,
                        'user' => [
                            'id' => $log->user->id ?? null,
                            'name' => $log->user->name ?? 'Unknown',
                            'email' => $log->user->email ?? 'N/A',
                            'role' => $log->user->role ?? $log->user_role ?? 'N/A',
                        ],
                        'action_type' => $log->action,
                        'description' => $this->generateDescription($log),
                        'created_at' => $log->created_at,
                    ];
                }),
        ];

        return response()->json($stats);
        } catch (\Exception $e) {
            \Log::error('Activity statistics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch activity statistics',
                'error' => $e->getMessage(),
                'total' => 0,
                'donations' => 0,
                'campaigns' => 0,
                'registrations' => 0,
                'logins_today' => 0,
                'unique_actions' => [],
                'by_action' => [],
                'recent_activities' => []
            ], 500);
        }
    }

    /**
     * Export logs to CSV
     */
    public function export(Request $request)
    {
        $query = ActivityLog::with('user:id,name,email,role')
            ->orderBy('created_at', 'desc');

        // Apply same filters as index
        if ($request->has('action_type') && $request->action_type !== 'all') {
            $query->where('action', $request->action_type);
        }

        // Filter by user role (donor or charity_admin)
        if ($request->has('target_type') && $request->target_type !== 'all') {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('role', $request->target_type);
            });
        }

        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $logs = $query->get();

        $filename = 'charityhub_activity_logs_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Add CSV headers
            fputcsv($file, ['ID', 'User', 'Email', 'Role', 'Action', 'Description', 'IP Address', 'Date']);

            // Add data rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->user->name ?? 'N/A',
                    $log->user->email ?? 'N/A',
                    $log->user->role ?? $log->user_role ?? 'N/A',
                    $log->action,
                    $this->generateDescription($log),
                    $log->ip_address ?? '',
                    $log->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Export logs to PDF
     */
    public function exportPDF(Request $request)
    {
        $query = ActivityLog::with('user:id,name,email,role')
            ->orderBy('created_at', 'desc');

        // Apply filters
        $filters = [];
        
        if ($request->has('action_type') && $request->action_type !== 'all') {
            $query->where('action', $request->action_type);
            $filters['action_type'] = $request->action_type;
        }

        if ($request->has('target_type') && $request->target_type !== 'all') {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('role', $request->target_type);
            });
            $filters['user_role'] = $request->target_type;
        }

        $startDate = $request->input('start_date', now()->subDays(30));
        $endDate = $request->input('end_date', now());
        
        if (is_string($startDate)) {
            $startDate = \Carbon\Carbon::parse($startDate);
        }
        if (is_string($endDate)) {
            $endDate = \Carbon\Carbon::parse($endDate);
        }

        $query->whereBetween('created_at', [$startDate, $endDate]);

        $logs = $query->limit(1000)->get(); // Limit to prevent huge PDFs

        // Prepare data
        $activities = $logs->map(function ($log) {
            return [
                'user_name' => $log->user->name ?? 'N/A',
                'user_role' => $log->user->role ?? $log->user_role ?? 'N/A',
                'action' => $log->action,
                'description' => $this->generateDescription($log),
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at,
            ];
        })->toArray();

        // Get statistics
        $byAction = \Illuminate\Support\Facades\DB::table('activity_logs')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('action', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();

        $data = [
            'period' => [
                'start' => $startDate->format('F d, Y'),
                'end' => $endDate->format('F d, Y'),
            ],
            'total_activities' => count($activities),
            'unique_users' => $logs->pluck('user_id')->unique()->count(),
            'login_count' => $logs->where('action', 'login')->count(),
            'donation_count' => $logs->where('action', 'donation_created')->count(),
            'activities' => $activities,
            'by_action' => $byAction,
            'filters' => $filters,
        ];

        $filename = \App\Helpers\ReportGenerator::generateFilename('activity_log');
        
        return \App\Helpers\ReportGenerator::generatePDF('reports.activity-log', $data, $filename);
    }
    
    /**
     * Generate a detailed human-readable description from the log
     */
    private function generateDescription($log)
    {
        $action = $log->action;
        $details = is_array($log->details) ? $log->details : json_decode($log->details, true) ?? [];
        
        // Generate detailed descriptions based on action type
        switch ($action) {
            // Authentication Actions
            case 'login':
                return 'Logged in to the system';
            
            case 'logout':
                return 'Logged out from the system';
            
            case 'register':
            case 'user_registered':
                $role = $details['role'] ?? 'user';
                return "Registered as " . ucfirst($role);
            
            case 'email_verified':
                return 'Email address verified';
            
            // Donation Actions - DETAILED
            case 'donation_created':
                $amount = isset($details['amount']) ? '₱' . number_format($details['amount'], 2) : 'unknown amount';
                $campaign = isset($details['campaign_id']) ? " (Campaign ID: {$details['campaign_id']})" : '';
                return "Made a donation of {$amount}{$campaign}";
            
            case 'donation_confirmed':
                $amount = isset($details['amount']) ? '₱' . number_format($details['amount'], 2) : '';
                $donation_id = isset($details['donation_id']) ? " (Donation #{$details['donation_id']})" : '';
                return "Confirmed donation {$amount}{$donation_id}";
            
            case 'donation_rejected':
                $reason = isset($details['reason']) ? " - Reason: {$details['reason']}" : '';
                $donation_id = isset($details['donation_id']) ? " #${details['donation_id']}" : '';
                return "Rejected donation{$donation_id}{$reason}";
            
            // Campaign Actions - DETAILED
            case 'campaign_created':
                $title = $details['campaign_title'] ?? 'Untitled Campaign';
                $campaign_id = isset($details['campaign_id']) ? " (ID: {$details['campaign_id']})" : '';
                return "Created campaign: \"{$title}\"{$campaign_id}";
            
            case 'campaign_updated':
                $title = $details['campaign_title'] ?? 'campaign';
                $fields = isset($details['changes']) ? ' - Updated: ' . implode(', ', array_slice($details['changes'], 0, 3)) : '';
                return "Updated {$title}{$fields}";
            
            case 'campaign_activated':
                $title = $details['campaign_title'] ?? 'campaign';
                return "Activated campaign: {$title}";
            
            case 'campaign_paused':
                $title = $details['campaign_title'] ?? 'campaign';
                return "Paused campaign: {$title}";
            
            case 'campaign_deleted':
                $title = $details['campaign_title'] ?? 'a campaign';
                return "Deleted campaign: {$title}";
            
            case 'campaign_completed':
                $title = $details['campaign_title'] ?? 'campaign';
                return "Completed campaign: {$title}";
            
            // Charity Actions - DETAILED
            case 'charity_created':
                $name = $details['charity_name'] ?? 'new charity';
                return "Registered charity: {$name}";
            
            case 'charity_updated':
                $name = $details['charity_name'] ?? 'charity';
                $fields = isset($details['changes']) ? ' - Updated: ' . implode(', ', array_slice($details['changes'], 0, 3)) : '';
                return "Updated charity profile: {$name}{$fields}";
            
            case 'charity_approved':
                $name = $details['charity_name'] ?? 'charity';
                return "Approved charity application: {$name}";
            
            case 'charity_rejected':
                $name = $details['charity_name'] ?? 'charity';
                $reason = isset($details['reason']) ? " - Reason: {$details['reason']}" : '';
                return "Rejected charity application: {$name}{$reason}";
            
            case 'charity_suspended':
                $name = $details['charity_name'] ?? 'charity';
                $reason = isset($details['reason']) ? " - Reason: {$details['reason']}" : '';
                return "Suspended charity: {$name}{$reason}";
            
            case 'charity_activated':
                $name = $details['charity_name'] ?? 'charity';
                return "Activated charity: {$name}";
            
            // Profile Actions - DETAILED
            case 'profile_updated':
                $fields = isset($details['updated_fields']) ? implode(', ', array_slice($details['updated_fields'], 0, 5)) : 'profile fields';
                return "Updated profile: {$fields}";
            
            case 'password_changed':
                return 'Changed account password';
            
            case 'email_changed':
                $old = $details['old_email'] ?? '';
                $new = $details['new_email'] ?? '';
                return "Changed email from {$old} to {$new}";
            
            // Account Actions - DETAILED
            case 'account_deactivated':
                return 'Deactivated account';
            
            case 'account_reactivated':
                return 'Reactivated account';
            
            case 'account_deleted':
                return 'Deleted account';
            
            // Post/Update Actions - DETAILED
            case 'post_created':
            case 'update_created':
                $charity_id = isset($details['charity_id']) ? " for Charity #{$details['charity_id']}" : '';
                return "Created new post/update{$charity_id}";
            
            case 'post_updated':
            case 'update_updated':
                return 'Updated post/update content';
            
            case 'post_deleted':
            case 'update_deleted':
                return 'Deleted post/update';
            
            // Comment Actions - DETAILED
            case 'comment_created':
                $campaign_id = isset($details['campaign_id']) ? " on Campaign #{$details['campaign_id']}" : '';
                return "Posted a comment{$campaign_id}";
            
            case 'comment_updated':
                return 'Edited comment';
            
            case 'comment_deleted':
                return 'Deleted comment';
            
            // Follow Actions - DETAILED
            case 'charity_followed':
                $charity_id = isset($details['charity_id']) ? " (Charity #{$details['charity_id']})" : '';
                return "Started following charity{$charity_id}";
            
            case 'charity_unfollowed':
                $charity_id = isset($details['charity_id']) ? " (Charity #{$details['charity_id']})" : '';
                return "Unfollowed charity{$charity_id}";
            
            // Document Actions - DETAILED
            case 'document_uploaded':
                $type = $details['doc_type'] ?? 'document';
                $charity_id = isset($details['charity_id']) ? " for Charity #{$details['charity_id']}" : '';
                return "Uploaded {$type}{$charity_id}";
            
            case 'document_approved':
                $doc_id = isset($details['document_id']) ? " #${details['document_id']}" : '';
                return "Approved document{$doc_id}";
            
            case 'document_rejected':
                $doc_id = isset($details['document_id']) ? " #${details['document_id']}" : '';
                $reason = isset($details['reason']) ? " - {$details['reason']}" : '';
                return "Rejected document{$doc_id}{$reason}";
            
            // Fund Usage Actions - DETAILED
            case 'fund_usage_created':
                $amount = isset($details['amount']) ? '₱' . number_format($details['amount'], 2) : '';
                $campaign_id = isset($details['campaign_id']) ? " for Campaign #{$details['campaign_id']}" : '';
                return "Logged fund usage of {$amount}{$campaign_id}";
            
            case 'fund_usage_updated':
                $amount = isset($details['amount']) ? '₱' . number_format($details['amount'], 2) : '';
                return "Updated fund usage record ({$amount})";
            
            case 'fund_usage_deleted':
                return 'Deleted fund usage record';
            
            // Refund Actions - DETAILED
            case 'refund_requested':
                $donation_id = isset($details['donation_id']) ? " for Donation #{$details['donation_id']}" : '';
                return "Requested refund{$donation_id}";
            
            case 'refund_approved':
                $amount = isset($details['amount']) ? '₱' . number_format($details['amount'], 2) : '';
                $donation_id = isset($details['donation_id']) ? " (Donation #{$details['donation_id']})" : '';
                return "Approved refund of {$amount}{$donation_id}";
            
            case 'refund_rejected':
                $donation_id = isset($details['donation_id']) ? " for Donation #{$details['donation_id']}" : '';
                $reason = isset($details['reason']) ? " - {$details['reason']}" : '';
                return "Rejected refund{$donation_id}{$reason}";
            
            // Admin Actions - DETAILED
            case 'user_suspended':
                $user_name = $details['suspended_user_name'] ?? 'user';
                $reason = isset($details['reason']) ? " - {$details['reason']}" : '';
                return "Suspended user: {$user_name}{$reason}";
            
            case 'user_activated':
                $user_name = $details['activated_user_name'] ?? 'user';
                return "Activated user: {$user_name}";
            
            case 'report_reviewed':
                $action_taken = $details['review_action'] ?? 'reviewed';
                return "Reviewed report - Action: {$action_taken}";
            
            // Default fallback
            default:
                $readable = ucwords(str_replace('_', ' ', $action));
                
                // Try to add context from details
                if (isset($details['amount'])) {
                    $readable .= ' - Amount: ₱' . number_format($details['amount'], 2);
                }
                if (isset($details['campaign_title'])) {
                    $readable .= ' - Campaign: ' . $details['campaign_title'];
                }
                if (isset($details['charity_name'])) {
                    $readable .= ' - Charity: ' . $details['charity_name'];
                }
                
                return $readable;
        }
    }
}
