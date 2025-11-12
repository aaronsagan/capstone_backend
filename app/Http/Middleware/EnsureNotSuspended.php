<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureNotSuspended
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Check if user is suspended
        if ($user->status === 'suspended' && $user->suspended_until && now()->lt($user->suspended_until)) {
            return response()->json([
                'message' => 'Your account has been suspended until ' . $user->suspended_until->format('M d, Y h:i A') . ' due to a violation of our terms. Please contact the administrator for details.',
                'status' => 'suspended',
                'suspended_until' => $user->suspended_until,
                'suspension_reason' => $user->suspension_reason,
            ], 403);
        }

        // Auto-clear expired suspension
        if ($user->status === 'suspended' && $user->suspended_until && now()->gte($user->suspended_until)) {
            $user->update([
                'status' => 'active',
                'suspended_until' => null,
                'suspension_reason' => null,
                'suspension_level' => null,
            ]);
            \App\Services\NotificationHelper::accountReactivated($user);
        }

        return $next($request);
    }
}
