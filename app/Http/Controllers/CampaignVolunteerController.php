<?php

namespace App\Http\Controllers;

use App\Models\CampaignVolunteer;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Services\NotificationHelper;

class CampaignVolunteerController extends Controller
{
    /**
     * Get volunteers for a campaign (Public - approved volunteers only)
     * Get all volunteer requests for charity admin
     */
    public function index(Request $request, $campaignId)
    {
        $campaign = Campaign::findOrFail($campaignId);
        $user = $request->user();
        
        $query = CampaignVolunteer::where('campaign_id', $campaignId)
            ->with(['user:id,name,email,profile_image']);

        // If charity owner, show all requests
        if ($user && $user->role === 'charity_admin' && $campaign->charity->owner_id === $user->id) {
            $volunteers = $query->orderBy('created_at', 'desc')->get();
        } else {
            // Public view: show only approved volunteers
            $volunteers = $query->approved()->orderBy('responded_at', 'desc')->get();
        }

        // Add profile picture URLs
        $volunteers->transform(function ($volunteer) {
            if ($volunteer->user && $volunteer->user->profile_image) {
                $volunteer->user->profile_picture_url = url('storage/' . $volunteer->user->profile_image);
            }
            return $volunteer;
        });

        return response()->json([
            'success' => true,
            'volunteers' => $volunteers
        ]);
    }

    /**
     * Request to volunteer for a campaign (Authenticated User)
     */
    public function store(Request $request, $campaignId)
    {
        $campaign = Campaign::findOrFail($campaignId);
        
        // Check if campaign accepts volunteers
        if (!$campaign->is_volunteer_based) {
            return response()->json([
                'success' => false,
                'message' => 'This campaign does not accept volunteer requests.'
            ], 422);
        }

        $validated = $request->validate([
            'message' => 'nullable|string|max:500'
        ]);

        // Check if already requested
        $existing = CampaignVolunteer::where('campaign_id', $campaignId)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You have already submitted a volunteer request for this campaign.',
                'status' => $existing->status
            ], 422);
        }

        $volunteer = CampaignVolunteer::create([
            'campaign_id' => $campaignId,
            'user_id' => $request->user()->id,
            'message' => $validated['message'] ?? null,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        // Notify charity
        NotificationHelper::volunteerRequestSubmitted($volunteer, $campaign);

        return response()->json([
            'success' => true,
            'message' => 'Your volunteer request has been submitted. The charity will review it soon.',
            'volunteer' => $volunteer->load('user')
        ], 201);
    }

    /**
     * Respond to volunteer request (Charity Admin only)
     */
    public function respond(Request $request, $volunteerId)
    {
        $volunteer = CampaignVolunteer::findOrFail($volunteerId);
        $campaign = $volunteer->campaign;
        $charity = $campaign->charity;
        
        // Check if user owns this charity
        if ($request->user()->id !== $charity->owner_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only manage volunteers for your own charity.'
            ], 403);
        }

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'response' => 'nullable|string|max:500'
        ]);

        $status = $validated['action'] === 'approve' ? 'approved' : 'rejected';

        $volunteer->update([
            'status' => $status,
            'charity_response' => $validated['response'] ?? null,
            'responded_at' => now(),
        ]);

        // Notify the volunteer
        NotificationHelper::volunteerRequestResponded($volunteer, $campaign, $status);

        return response()->json([
            'success' => true,
            'message' => $status === 'approved' 
                ? 'Volunteer request approved. The user has been notified.'
                : 'Volunteer request rejected. The user has been notified.',
            'volunteer' => $volunteer->fresh(['user', 'campaign'])
        ]);
    }

    /**
     * Get user's volunteer requests (Authenticated User)
     */
    public function myRequests(Request $request)
    {
        $requests = CampaignVolunteer::where('user_id', $request->user()->id)
            ->with(['campaign.charity:id,name,logo_path'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'requests' => $requests
        ]);
    }

    /**
     * Cancel volunteer request (Authenticated User)
     */
    public function cancel(Request $request, $volunteerId)
    {
        $volunteer = CampaignVolunteer::findOrFail($volunteerId);
        
        // Check if user owns this request
        if ($request->user()->id !== $volunteer->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 403);
        }

        // Can only cancel pending requests
        if ($volunteer->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'You can only cancel pending requests.'
            ], 422);
        }

        $volunteer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Volunteer request cancelled successfully.'
        ]);
    }
}
