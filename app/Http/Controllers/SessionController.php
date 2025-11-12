<?php

namespace App\Http\Controllers;

use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    /**
     * Get all active sessions for the authenticated user
     */
    public function index(Request $request)
    {
        $currentTokenId = $request->user()->currentAccessToken()->id ?? null;

        $sessions = UserSession::where('user_id', $request->user()->id)
            ->where('last_activity', '>', now()->subDays(30)) // Only show sessions active in last 30 days
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function($session) use ($currentTokenId) {
                $session->is_current = ($session->token_id == $currentTokenId);
                return $session;
            });

        return response()->json($sessions);
    }

    /**
     * Revoke a specific session
     */
    public function destroy(Request $request, $id)
    {
        $session = UserSession::where('user_id', $request->user()->id)
            ->findOrFail($id);

        // Find and revoke the token
        $token = $request->user()->tokens()->where('id', $session->token_id)->first();
        if ($token) {
            $token->delete();
        }

        // Delete the session record
        $session->delete();

        return response()->json([
            'success' => true,
            'message' => 'Session revoked successfully'
        ]);
    }

    /**
     * Revoke all other sessions except current
     */
    public function destroyAll(Request $request)
    {
        $currentTokenId = $request->user()->currentAccessToken()->id ?? null;

        // Get all sessions except current
        $sessions = UserSession::where('user_id', $request->user()->id)
            ->where('token_id', '!=', $currentTokenId)
            ->get();

        // Revoke all tokens
        foreach ($sessions as $session) {
            $token = $request->user()->tokens()->where('id', $session->token_id)->first();
            if ($token) {
                $token->delete();
            }
            $session->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'All other sessions revoked successfully',
            'count' => $sessions->count()
        ]);
    }
}
