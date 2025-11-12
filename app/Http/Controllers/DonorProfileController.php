<?php

namespace App\Http\Controllers;

use App\Http\Resources\DonorProfileResource;
use App\Http\Resources\DonorDonationResource;
use App\Http\Resources\DonorMilestoneResource;
use App\Models\User;
use App\Models\DonorMilestone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DonorProfileController extends Controller
{
    /**
     * Get donor profile by ID
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $donor = User::with(['donorProfile', 'donations', 'savedItems'])
            ->where('role', 'donor')
            ->whereNotNull('email_verified_at') // Only show verified donors
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new DonorProfileResource($donor),
        ]);
    }

    /**
     * Get donor activity (donations) with pagination
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function activity(Request $request, $id)
    {
        $donor = User::where('role', 'donor')->findOrFail($id);
        
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
     * Get donor milestones
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function milestones(Request $request, $id)
    {
        $donor = User::where('role', 'donor')->findOrFail($id);
        
        $milestones = DonorMilestone::forDonor($id)
            ->orderByRaw('achieved_at IS NULL') // Achieved first
            ->orderBy('achieved_at', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => DonorMilestoneResource::collection($milestones),
        ]);
    }

    /**
     * Update donor profile (owner only)
     * 
     * @param Request $request
     * @param int $id
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

        $donor = User::where('role', 'donor')->findOrFail($id);

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

        $donor = User::where('role', 'donor')->findOrFail($id);

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
}
