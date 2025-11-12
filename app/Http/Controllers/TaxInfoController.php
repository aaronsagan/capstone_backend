<?php

namespace App\Http\Controllers;

use App\Events\TaxInfoUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TaxInfoController extends Controller
{
    /**
     * Get tax information for authenticated user
     */
    public function show(Request $request)
    {
        $user = $request->user();
        
        // For now, return mock data. In production, fetch from database
        // Assuming tax info is stored in JSON or related table
        $taxInfo = $user->tax_info ?? [];
        
        return response()->json([
            'tax_info' => $taxInfo
        ]);
    }

    /**
     * Update tax information
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'tin' => 'required|string|max:50',
            'business_name' => 'nullable|string|max:255',
            'address' => 'required|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
        ]);

        $user = $request->user();

        // In production, save to database
        // For now, we'll just trigger the event
        
        // Format address for email
        $fullAddress = $data['address'];
        if (!empty($data['city'])) {
            $fullAddress .= ', ' . $data['city'];
        }
        if (!empty($data['province'])) {
            $fullAddress .= ', ' . $data['province'];
        }
        if (!empty($data['postal_code'])) {
            $fullAddress .= ' ' . $data['postal_code'];
        }

        $taxInfoForEmail = [
            'tin' => $data['tin'],
            'business_name' => $data['business_name'] ?? $user->name,
            'address' => $fullAddress,
        ];

        // Dispatch event to send email
        event(new TaxInfoUpdated($user, $taxInfoForEmail));

        Log::info('Tax info updated', [
            'user_id' => $user->id,
            'tin' => $data['tin'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tax information updated successfully. Confirmation email sent.',
            'tax_info' => $data
        ]);
    }
}
