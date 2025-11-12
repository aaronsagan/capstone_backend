<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DonorProfileResource;
use App\Http\Resources\DonorDonationResource;
use App\Http\Resources\DonorMilestoneResource;
use App\Models\User;
use App\Models\Donation;
use App\Models\DonorMilestone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DonorProfileController extends Controller
{
    /**
     * Get donor profile with computed metrics
     * 
     * @param int $id Donor user ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        // Find user first
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        // Check if user is a donor
        if ($user->role !== 'donor') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a donor'
            ], 404);
        }
        
        // Load relationships
        $donor = User::with(['donorProfile', 'donations', 'savedItems'])
            ->find($id);
        
        return response()->json([
            'success' => true,
            'data' => new DonorProfileResource($donor)
        ]);
    }

    /**
     * Get donor's activity (paginated donations)
     * 
     * @param int $id Donor user ID
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function activity(Request $request, $id)
    {
        $donor = User::find($id);
        
        if (!$donor || $donor->role !== 'donor') {
            return response()->json([
                'success' => false,
                'message' => 'Donor not found'
            ], 404);
        }
        
        // Check privacy: only owner can see their own donations if they're private
        $isOwner = $request->user() && $request->user()->id === (int)$id;
        
        $perPage = min((int)$request->get('per_page', 10), 50);
        
        $donations = $donor->donations()
            ->with(['campaign.charity', 'charity'])
            ->whereIn('status', ['completed', 'auto_verified', 'manual_verified'])
            ->when(!$isOwner, function($query) {
                // Non-owners can only see non-anonymous donations
                $query->where(function($q) {
                    $q->where('is_anonymous', false)
                      ->orWhereNull('is_anonymous');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => DonorDonationResource::collection($donations->items()),
            'pagination' => [
                'current_page' => $donations->currentPage(),
                'last_page' => $donations->lastPage(),
                'per_page' => $donations->perPage(),
                'total' => $donations->total(),
                'has_more' => $donations->hasMorePages(),
            ],
        ]);
    }

    /**
     * Get donor's milestones/achievements
     * 
     * @param int $id Donor user ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function milestones($id)
    {
        // Verify donor exists
        $donor = User::find($id);
        if (!$donor || $donor->role !== 'donor') {
            return response()->json([
                'success' => false,
                'message' => 'Donor not found'
            ], 404);
        }
        
        $milestones = DonorMilestone::where('donor_id', $id)
            ->orderByRaw('achieved_at IS NULL') // Achieved first
            ->orderBy('achieved_at', 'desc')
            ->get();
        
        return response()->json([
            'data' => DonorMilestoneResource::collection($milestones)
        ]);
    }

    /**
     * Update donor profile (bio, contact)
     * Only owner can update
     * 
     * @param Request $request
     * @param int $id Donor user ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // Authorization: only owner can update
        if (!$request->user() || $request->user()->id !== (int)$id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only update your own profile.',
            ], 403);
        }

        $donor = User::find($id);
        
        if (!$donor || $donor->role !== 'donor') {
            return response()->json([
                'success' => false,
                'message' => 'Donor not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'bio' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'cause_preferences' => 'nullable|array',
            'cause_preferences.*' => 'string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Update user table fields
        if (isset($validated['phone'])) {
            $donor->phone = $validated['phone'];
        }
        if (isset($validated['address'])) {
            $donor->address = $validated['address'];
        }
        $donor->save();

        // Update donor profile fields
        if (!$donor->donorProfile) {
            $donor->donorProfile()->create([]);
        }

        $profileData = [];
        if (isset($validated['bio'])) {
            $profileData['bio'] = $validated['bio'];
        }
        if (isset($validated['address'])) {
            $profileData['full_address'] = $validated['address'];
        }
        if (isset($validated['date_of_birth'])) {
            $profileData['date_of_birth'] = $validated['date_of_birth'];
        }
        if (isset($validated['gender'])) {
            $profileData['gender'] = $validated['gender'];
        }
        if (isset($validated['cause_preferences'])) {
            $profileData['cause_preferences'] = $validated['cause_preferences'];
        }

        if (!empty($profileData)) {
            $donor->donorProfile()->update($profileData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => new DonorProfileResource($donor->fresh(['donorProfile'])),
        ]);
    }
    
    /**
     * Update donor profile or cover image
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateImage(Request $request, $id)
    {
        // Authorization: only owner can update
        if (!$request->user() || $request->user()->id !== (int)$id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $donor = User::find($id);
        
        if (!$donor || $donor->role !== 'donor') {
            return response()->json([
                'success' => false,
                'message' => 'Donor not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB
            'type' => 'required|in:profile,cover',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $type = $request->input('type');
        $image = $request->file('image');

        // Delete old image
        if ($type === 'profile' && $donor->profile_image) {
            Storage::disk('public')->delete($donor->profile_image);
        } elseif ($type === 'cover' && $donor->cover_image) {
            Storage::disk('public')->delete($donor->cover_image);
        }

        // Store new image
        $path = $image->store('donors/' . $id, 'public');

        // Update user
        if ($type === 'profile') {
            $donor->profile_image = $path;
        } else {
            $donor->cover_image = $path;
        }
        $donor->save();

        return response()->json([
            'success' => true,
            'message' => ucfirst($type) . ' image updated successfully',
            'data' => [
                'url' => url('storage/' . $path),
            ],
        ]);
    }

    /**
     * Get donor's badges/recognition
     * 
     * @param int $id Donor user ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function badges($id)
    {
        $donor = User::find($id);
        
        if (!$donor || $donor->role !== 'donor') {
            return response()->json([
                'success' => false,
                'message' => 'Donor not found'
            ], 404);
        }
        
        // Calculate donor stats (only count verified/completed donations, excluding refunded)
        $verifiedStatuses = ['completed', 'auto_verified', 'manual_verified'];
        $totalDonated = Donation::where('donor_id', $id)
            ->whereIn('status', $verifiedStatuses)
            ->where('is_refunded', false)
            ->sum('amount');
        $donationCount = Donation::where('donor_id', $id)
            ->whereIn('status', $verifiedStatuses)
            ->where('is_refunded', false)
            ->count();
        $campaignsSupported = Donation::where('donor_id', $id)
            ->whereIn('status', $verifiedStatuses)
            ->where('is_refunded', false)
            ->distinct('campaign_id')
            ->count('campaign_id');
        
        // Define available badges and check if earned
        $badges = [
            [
                'name' => 'First Donation',
                'description' => 'Made your first charitable donation',
                'icon' => 'Heart',
                'earned' => $donationCount >= 1,
            ],
            [
                'name' => 'Generous Giver',
                'description' => 'Donated over ₱10,000',
                'icon' => 'Award',
                'earned' => $totalDonated >= 10000,
            ],
            [
                'name' => 'Community Supporter',
                'description' => 'Supported 5 different campaigns',
                'icon' => 'Users',
                'earned' => $campaignsSupported >= 5,
            ],
            [
                'name' => 'Super Donor',
                'description' => 'Donated over ₱50,000',
                'icon' => 'Trophy',
                'earned' => $totalDonated >= 50000,
            ],
            [
                'name' => 'Active Supporter',
                'description' => 'Made 10 or more donations',
                'icon' => 'Zap',
                'earned' => $donationCount >= 10,
            ],
            [
                'name' => 'Campaign Champion',
                'description' => 'Supported 10 different campaigns',
                'icon' => 'Flag',
                'earned' => $campaignsSupported >= 10,
            ],
        ];
        
        return response()->json([
            'data' => array_map(function($badge) {
                return (object) $badge;
            }, $badges)
        ]);
    }
}
