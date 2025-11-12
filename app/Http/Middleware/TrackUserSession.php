<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\UserSession;
use Jenssegers\Agent\Agent;

class TrackUserSession
{
    /**
     * Handle an incoming request and track the user session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only track authenticated requests
        if ($request->user() && $request->user()->currentAccessToken()) {
            $this->trackSession($request);
        }

        return $next($request);
    }

    /**
     * Track or update the user session
     */
    protected function trackSession(Request $request)
    {
        $user = $request->user();
        $token = $request->user()->currentAccessToken();
        
        if (!$token) {
            return;
        }

        // Get device information
        $agent = new Agent();
        $agent->setUserAgent($request->header('User-Agent'));
        
        $deviceType = $this->getDeviceType($agent);
        $browser = $agent->browser();
        $platform = $agent->platform();
        $ipAddress = $request->ip();

        // Find or create session
        $session = UserSession::where('user_id', $user->id)
            ->where('token_id', $token->id)
            ->first();

        if ($session) {
            // Update existing session
            $session->update([
                'last_activity' => now(),
                'ip_address' => $ipAddress,
            ]);
        } else {
            // Create new session
            UserSession::create([
                'user_id' => $user->id,
                'token_id' => $token->id,
                'device_type' => $deviceType,
                'browser' => $browser ?: 'Unknown',
                'platform' => $platform ?: 'Unknown',
                'ip_address' => $ipAddress,
                'last_activity' => now(),
            ]);
        }
    }

    /**
     * Determine device type from user agent
     */
    protected function getDeviceType(Agent $agent): string
    {
        if ($agent->isMobile()) {
            return 'mobile';
        }
        
        if ($agent->isTablet()) {
            return 'tablet';
        }
        
        return 'desktop';
    }
}
