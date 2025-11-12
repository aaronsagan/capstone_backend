<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    /**
     * Get user's notification preferences
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        
        // Get existing preferences
        $existing = NotificationPreference::where('user_id', $userId)->get()->keyBy('category');
        
        // Merge with defaults
        $defaults = NotificationPreference::getDefaultCategories();
        $preferences = [];
        
        foreach ($defaults as $category => $defaultSettings) {
            if (isset($existing[$category])) {
                $preferences[] = [
                    'category' => $category,
                    'email' => $existing[$category]->email,
                    'push' => $existing[$category]->push,
                    'sms' => $existing[$category]->sms,
                    'frequency' => $existing[$category]->frequency,
                ];
            } else {
                $preferences[] = array_merge(['category' => $category], $defaultSettings);
            }
        }
        
        return response()->json(['preferences' => $preferences]);
    }

    /**
     * Update notification preferences
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.category' => 'required|string',
            'preferences.*.email' => 'required|boolean',
            'preferences.*.push' => 'required|boolean',
            'preferences.*.sms' => 'required|boolean',
            'preferences.*.frequency' => 'required|in:instant,daily,weekly,monthly',
        ]);

        $userId = $request->user()->id;

        foreach ($validated['preferences'] as $pref) {
            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $userId,
                    'category' => $pref['category'],
                ],
                [
                    'email' => $pref['email'],
                    'push' => $pref['push'],
                    'sms' => $pref['sms'],
                    'frequency' => $pref['frequency'],
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully',
        ]);
    }
}
