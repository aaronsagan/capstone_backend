<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ReactivationRequest;
use Illuminate\Support\Facades\Mail;

class UserManagementController extends Controller
{
    /**
     * Get all reactivation requests
     */
    public function getReactivationRequests(Request $request)
    {
        $requests = ReactivationRequest::with(['user', 'reviewer'])
            ->orderBy('requested_at', 'desc')
            ->paginate(20);

        return response()->json($requests);
    }

    /**
     * Approve reactivation request
     */
    public function approveReactivation(Request $request, $id)
    {
        $reactivationRequest = ReactivationRequest::with('user')->findOrFail($id);

        if ($reactivationRequest->status !== 'pending') {
            return response()->json([
                'message' => 'This request has already been processed'
            ], 422);
        }

        $user = $reactivationRequest->user;

        // Reactivate the user account
        $user->update(['status' => 'active']);

        // Update reactivation request
        $reactivationRequest->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
            'admin_notes' => $request->input('notes')
        ]);

        // Send email to user immediately
        Mail::to($user->email)->send(
            new \App\Mail\Security\AccountReactivatedMail($user)
        );

        // Notify user via database notification
        \App\Services\NotificationHelper::accountReactivated($user);

        return response()->json([
            'success' => true,
            'message' => 'Account reactivated successfully'
        ]);
    }

    /**
     * Reject reactivation request
     */
    public function rejectReactivation(Request $request, $id)
    {
        $validated = $request->validate([
            'notes' => 'required|string|max:1000'
        ]);

        $reactivationRequest = ReactivationRequest::with('user')->findOrFail($id);

        if ($reactivationRequest->status !== 'pending') {
            return response()->json([
                'message' => 'This request has already been processed'
            ], 422);
        }

        // Update reactivation request
        $reactivationRequest->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
            'admin_notes' => $validated['notes']
        ]);

        // Send rejection email to user immediately
        Mail::to($reactivationRequest->user->email)->send(
            new \App\Mail\Security\ReactivationRejectedMail($reactivationRequest->user, $validated['notes'])
        );

        return response()->json([
            'success' => true,
            'message' => 'Reactivation request rejected'
        ]);
    }
}
