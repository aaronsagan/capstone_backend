<?php

namespace App\Http\Controllers;

use App\Models\RecurringDonation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\RecurringDonationUpdateMail;

class RecurringDonationController extends Controller
{
    /**
     * Get user's recurring donations
     */
    public function index(Request $request)
    {
        $recurring = $request->user()
            ->recurringDonations()
            ->with(['campaign', 'charity'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($recurring);
    }

    /**
     * Update recurring donation (pause/resume/update amount)
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:active,paused',
            'amount' => 'nullable|numeric|min:10',
            'interval' => 'nullable|in:weekly,monthly,quarterly,yearly',
        ]);

        $recurring = $request->user()
            ->recurringDonations()
            ->findOrFail($id);

        $action = null;

        // Handle status change
        if (isset($validated['status'])) {
            if ($validated['status'] === 'paused' && $recurring->status === 'active') {
                $recurring->pause();
                $action = 'paused';
            } elseif ($validated['status'] === 'active' && $recurring->status === 'paused') {
                $recurring->resume();
                $action = 'resumed';
            }
        }

        // Handle amount or interval update
        if (isset($validated['amount']) || isset($validated['interval'])) {
            $recurring->update(array_filter([
                'amount' => $validated['amount'] ?? null,
                'interval' => $validated['interval'] ?? null,
            ]));
            $action = $action ?? 'updated';
        }

        // Send update email
        if ($action) {
            try {
                Mail::to($request->user()->email)->queue(
                    new RecurringDonationUpdateMail($request->user(), $recurring, $action)
                );
            } catch (\Exception $e) {
                Log::error('Failed to send recurring donation update email', [
                    'recurring_id' => $recurring->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Recurring donation {$action} successfully",
            'recurring' => $recurring->fresh(['campaign', 'charity']),
        ]);
    }

    /**
     * Cancel recurring donation
     */
    public function destroy(Request $request, $id)
    {
        $recurring = $request->user()
            ->recurringDonations()
            ->findOrFail($id);

        $recurring->cancel();

        // Send cancellation email
        try {
            Mail::to($request->user()->email)->queue(
                new RecurringDonationUpdateMail($request->user(), $recurring, 'cancelled')
            );
        } catch (\Exception $e) {
            Log::error('Failed to send recurring donation cancellation email', [
                'recurring_id' => $recurring->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Recurring donation cancelled successfully',
        ]);
    }
}
