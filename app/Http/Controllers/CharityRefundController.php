<?php

namespace App\Http\Controllers;

use App\Models\{RefundRequest, Charity};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Services\{NotificationService, SecurityService};
use Illuminate\Support\Facades\Schema;
use App\Mail\RefundResponseMail;

class CharityRefundController extends Controller
{
    protected $notificationService;
    protected $securityService;

    public function __construct(NotificationService $notificationService, SecurityService $securityService)
    {
        $this->notificationService = $notificationService;
        $this->securityService = $securityService;
    }

    /**
     * Get refund requests for the charity
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get the charity owned by this user
        $charity = Charity::where('owner_id', $user->id)->first();

        if (!$charity) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own a charity.'
            ], 403);
        }

        $status = $request->get('status', 'all');

        $query = RefundRequest::with(['donation.campaign', 'user', 'reviewer'])
            ->orderBy('created_at', 'desc');

        if (Schema::hasColumn('refund_requests', 'charity_id')) {
            $query->where('charity_id', $charity->id);
        } else {
            // Fallback for older schema: filter via donations table
            $query->whereHas('donation', function($q) use ($charity) {
                $q->where('charity_id', $charity->id);
            });
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $refunds = $query->get();

        return response()->json([
            'success' => true,
            'refunds' => $refunds,
            'charity' => $charity,
        ]);
    }

    /**
     * Get single refund request details
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        // Get the charity owned by this user
        $charity = Charity::where('owner_id', $user->id)->first();

        if (!$charity) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own a charity.'
            ], 403);
        }

        $refundQuery = RefundRequest::with(['donation.campaign', 'user', 'reviewer'])
            ->where('id', $id);
        if (Schema::hasColumn('refund_requests', 'charity_id')) {
            $refundQuery->where('charity_id', $charity->id);
        } else {
            $refundQuery->whereHas('donation', function($q) use ($charity) {
                $q->where('charity_id', $charity->id);
            });
        }
        $refund = $refundQuery->first();

        if (!$refund) {
            return response()->json([
                'success' => false,
                'message' => 'Refund request not found or you do not have access.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'refund' => $refund
        ]);
    }

    /**
     * Respond to refund request (approve or deny)
     */
    public function respond(Request $request, $id)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,deny',
            'response' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        // Get the charity owned by this user
        $charity = Charity::where('owner_id', $user->id)->first();

        if (!$charity) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own a charity.'
            ], 403);
        }

        $refund = RefundRequest::with(['donation.charity', 'donation.campaign', 'user'])
            ->where('id', $id)
            ->where('charity_id', $charity->id)
            ->first();

        if (!$refund) {
            return response()->json([
                'success' => false,
                'message' => 'Refund request not found or you do not have access.'
            ], 404);
        }

        // Check if already reviewed
        if ($refund->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This refund request has already been reviewed.',
                'current_status' => $refund->status
            ], 422);
        }

        $action = $validated['action'];
        $newStatus = $action === 'approve' ? 'approved' : 'denied';

        // Update refund request
        $refund->update([
            'status' => $newStatus,
            'charity_response' => $validated['response'] ?? null,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ]);

        // If approved, mark the donation as refunded
        if ($action === 'approve') {
            $donation = $refund->donation;
            
            // Store original values for logging
            $originalStatus = $donation->status;
            $campaignId = $donation->campaign_id;
            $charityId = $donation->charity_id;
            $amount = $donation->amount;
            
            try {
                // Update donation status
                $donation->update([
                    'status' => 'refunded',  // Change status to refunded
                    'is_refunded' => true,
                    'refunded_at' => now(),
                ]);
                
                // Verify the update was successful
                $donation->refresh();
                if ($donation->status !== 'refunded' || !$donation->is_refunded) {
                    throw new \Exception('Failed to update donation status to refunded');
                }
                
                // The Donation model's boot method will automatically recalculate totals
                // But we'll manually trigger it as well for extra safety
                if ($campaignId) {
                    \App\Models\Campaign::find($campaignId)?->recalculateTotals();
                }
                if ($charityId) {
                    \App\Models\Charity::find($charityId)?->recalculateTotals();
                }
                
                Log::info('Refund approved and processed successfully', [
                    'refund_id' => $refund->id,
                    'donation_id' => $donation->id,
                    'amount' => $amount,
                    'campaign_id' => $campaignId,
                    'charity_id' => $charityId,
                    'original_status' => $originalStatus,
                    'new_status' => $donation->status,
                    'is_refunded' => $donation->is_refunded,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to process refund approval', [
                    'refund_id' => $refund->id,
                    'donation_id' => $donation->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                // Rollback refund status
                $refund->update([
                    'status' => 'pending',
                    'charity_response' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process refund. Please try again or contact support.',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        // Log the action
        $this->securityService->logActivity($user, "refund_{$newStatus}", [
            'refund_request_id' => $refund->id,
            'donation_id' => $refund->donation_id,
            'donor_id' => $refund->user_id,
            'amount' => $refund->refund_amount,
            'response' => $validated['response'] ?? null,
        ]);

        // Send email notification to donor
        try {
            Mail::to($refund->user->email)->queue(
                new RefundResponseMail($refund->user, $refund->donation, $refund)
            );
        } catch (\Exception $e) {
            Log::error('Failed to send refund response email', [
                'refund_request_id' => $refund->id,
                'error' => $e->getMessage(),
            ]);
        }

        $message = $action === 'approve' 
            ? 'Refund request approved. The donor has been notified.'
            : 'Refund request denied. The donor has been notified.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'refund' => $refund->fresh(['donation.charity', 'donation.campaign', 'user', 'reviewer'])
        ]);
    }

    /**
     * Get refund statistics for the charity
     */
    public function statistics(Request $request)
    {
        $user = $request->user();

        // Get the charity owned by this user
        $charity = Charity::where('owner_id', $user->id)->first();

        if (!$charity) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own a charity.'
            ], 403);
        }

        try {
            // Build base query depending on schema
            $base = RefundRequest::query();
            if (Schema::hasColumn('refund_requests', 'charity_id')) {
                $base->where('charity_id', $charity->id);
            } else {
                $base->whereHas('donation', function($q) use ($charity) {
                    $q->where('charity_id', $charity->id);
                });
            }

            $stats = [
                'total' => (clone $base)->count(),
                'pending' => (clone $base)->pending()->count(),
                'approved' => (clone $base)->approved()->count(),
                'denied' => (clone $base)->denied()->count(),
                'total_amount_requested' => (clone $base)->sum('refund_amount'),
                'total_amount_approved' => (clone $base)->approved()->sum('refund_amount'),
            ];

            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to compute refund statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => true,
                'statistics' => [
                    'total' => 0,
                    'pending' => 0,
                    'approved' => 0,
                    'denied' => 0,
                    'total_amount_requested' => 0,
                    'total_amount_approved' => 0,
                ],
                'warning' => 'Statistics temporarily unavailable',
            ]);
        }
    }
}
