<?php

namespace App\Http\Controllers;

use App\Models\CharityOfficer;
use App\Models\Charity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CharityOfficerController extends Controller
{
    /**
     * Get all officers for a charity (Public)
     */
    public function index($charityId)
    {
        $officers = CharityOfficer::where('charity_id', $charityId)
            ->active()
            ->ordered()
            ->get();

        // Add full URL for profile images
        $officers->transform(function ($officer) {
            if ($officer->profile_image_path) {
                $officer->profile_image_url = url('storage/' . $officer->profile_image_path);
            }
            return $officer;
        });

        return response()->json([
            'success' => true,
            'officers' => $officers
        ]);
    }

    /**
     * Store a new officer (Charity Admin only)
     */
    public function store(Request $request, $charityId)
    {
        $charity = Charity::findOrFail($charityId);
        
        // Check if user owns this charity
        if ($request->user()->id !== $charity->owner_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only manage officers for your own charity.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $validated['charity_id'] = $charityId;
        $validated['is_active'] = true;

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            $validated['profile_image_path'] = $request->file('profile_image')->store('charity_officers', 'public');
        }

        $officer = CharityOfficer::create($validated);

        // Add full URL
        if ($officer->profile_image_path) {
            $officer->profile_image_url = url('storage/' . $officer->profile_image_path);
        }

        return response()->json([
            'success' => true,
            'message' => 'Officer added successfully',
            'officer' => $officer
        ], 201);
    }

    /**
     * Update an officer (Charity Admin only)
     */
    public function update(Request $request, $officerId)
    {
        $officer = CharityOfficer::findOrFail($officerId);
        $charity = $officer->charity;
        
        // Check if user owns this charity
        if ($request->user()->id !== $charity->owner_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only manage officers for your own charity.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'position' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // Delete old image if exists
            if ($officer->profile_image_path) {
                Storage::disk('public')->delete($officer->profile_image_path);
            }
            $validated['profile_image_path'] = $request->file('profile_image')->store('charity_officers', 'public');
        }

        $officer->update($validated);

        // Add full URL
        if ($officer->profile_image_path) {
            $officer->profile_image_url = url('storage/' . $officer->profile_image_path);
        }

        return response()->json([
            'success' => true,
            'message' => 'Officer updated successfully',
            'officer' => $officer->fresh()
        ]);
    }

    /**
     * Delete an officer (Charity Admin only)
     */
    public function destroy(Request $request, $officerId)
    {
        $officer = CharityOfficer::findOrFail($officerId);
        $charity = $officer->charity;
        
        // Check if user owns this charity
        if ($request->user()->id !== $charity->owner_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only manage officers for your own charity.'
            ], 403);
        }

        // Delete profile image if exists
        if ($officer->profile_image_path) {
            Storage::disk('public')->delete($officer->profile_image_path);
        }

        $officer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Officer deleted successfully'
        ]);
    }
}
