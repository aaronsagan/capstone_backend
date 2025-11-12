<?php

namespace App\Http\Controllers;

use App\Models\CharityFollow;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    /**
     * Get list of charities followed by the authenticated user
     */
    public function index(Request $request)
    {
        try {
            $follows = $request->user()
                ->charityFollows()
                ->where('is_following', true) // Only active follows
                ->with(['charity' => function($query) {
                    $query->select('id', 'name', 'logo_path', 'description', 'city', 'province');
                }])
                ->latest()
                ->get();

            // Transform to match frontend expected format
            $follows = $follows->map(function($follow) {
                if ($follow->charity) {
                    $follow->charity->tagline = $follow->charity->description ?? '';
                }
                return $follow;
            });

            return response()->json($follows);
        } catch (\Exception $e) {
            \Log::error('Follow index error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Failed to fetch followed charities'
            ], 500);
        }
    }

    /**
     * Unfollow a charity
     */
    public function destroy(Request $request, $id)
    {
        $follow = $request->user()
            ->charityFollows()
            ->findOrFail($id);

        $follow->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully unfollowed charity'
        ]);
    }
}
