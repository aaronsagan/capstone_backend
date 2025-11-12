<?php

namespace App\Http\Controllers;

use App\Models\SavedItem;
use App\Models\Campaign;
use App\Models\Charity;
use App\Models\CharityPost;
use App\Models\Update;
use Illuminate\Http\Request;

class SavedItemController extends Controller
{
    /**
     * Get user's saved items (campaigns, charities, posts)
     */
    public function index(Request $request)
    {
        try {
            $saved = $request->user()
                ->savedItems()
                ->with(['savable'])
                ->latest()
                ->get();

            // Group by type and enrich data
            $grouped = [
                'campaigns' => [],
                'charities' => [],
                'posts' => []
            ];

            foreach ($saved as $item) {
                if (!$item->savable) continue;

                $type = class_basename($item->savable_type);
                
                switch ($type) {
                    case 'Campaign':
                        $item->savable->load('charity:id,name,logo_path');
                        if ($item->savable->end_date) {
                            $item->savable->days_remaining = now()->diffInDays($item->savable->end_date, false);
                        }
                        $grouped['campaigns'][] = $item;
                        break;
                    
                    case 'Charity':
                        $grouped['charities'][] = $item;
                        break;
                    
                    case 'CharityPost':
                    case 'Update':
                        $item->savable->load('charity:id,name,logo_path');
                        $grouped['posts'][] = $item;
                        break;
                }
            }

            return response()->json([
                'success' => true,
                'all' => $saved,
                'grouped' => $grouped
            ]);
        } catch (\Exception $e) {
            \Log::error('Saved items error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Failed to fetch saved items'
            ], 500);
        }
    }

    /**
     * Save an item (campaign, charity, or post)
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'savable_id' => 'required|integer',
                'savable_type' => 'required|in:campaign,charity,post',
            ]);

            $modelMap = [
                'campaign' => Campaign::class,
                'charity' => Charity::class,
                'post' => Update::class,
            ];

            $modelClass = $modelMap[$validated['savable_type']];

            // Check if the savable item exists
            if (!$modelClass::find($validated['savable_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => ucfirst($validated['savable_type']) . ' not found'
                ], 404);
            }

            // Use firstOrCreate to handle duplicates gracefully
            $saved = SavedItem::firstOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'savable_id' => $validated['savable_id'],
                    'savable_type' => $modelClass,
                ],
                [
                    'reminded_at' => null,
                ]
            );

            // Load the relationship
            $saved->load('savable');

            $wasRecentlyCreated = $saved->wasRecentlyCreated;

            return response()->json([
                'success' => true,
                'message' => $wasRecentlyCreated 
                    ? ucfirst($validated['savable_type']) . ' saved successfully'
                    : ucfirst($validated['savable_type']) . ' already saved',
                'saved' => $saved,
                'was_recently_created' => $wasRecentlyCreated
            ], $wasRecentlyCreated ? 201 : 200);

        } catch (\Illuminate\Database\QueryException $e) {
            // Handle duplicate key exception gracefully
            if ($e->getCode() === '23000') {
                \Log::warning('Duplicate save attempt: ' . $e->getMessage());
                
                // Try to find the existing record
                $existing = SavedItem::where('user_id', $request->user()->id)
                    ->where('savable_id', $validated['savable_id'])
                    ->where('savable_type', $modelClass)
                    ->with('savable')
                    ->first();
                
                if ($existing) {
                    return response()->json([
                        'success' => true,
                        'message' => ucfirst($validated['savable_type']) . ' already saved',
                        'saved' => $existing,
                        'was_recently_created' => false
                    ], 200);
                }
            }
            
            \Log::error('SavedItem store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save item. Please try again.'
            ], 500);
            
        } catch (\Exception $e) {
            \Log::error('SavedItem store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save item. Please try again.'
            ], 500);
        }
    }

    /**
     * Remove a saved campaign
     */
    public function destroy(Request $request, $id)
    {
        $saved = $request->user()
            ->savedItems()
            ->findOrFail($id);

        $saved->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campaign removed from saved items'
        ]);
    }
}
