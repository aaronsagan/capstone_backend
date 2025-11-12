<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\SecurityController;

/**
 * Charity Security Controller
 * 
 * Handles Two-Factor Authentication (2FA) for charity admins.
 * Delegates to SecurityController since charity admins are Users with role='charity_admin'.
 */
class CharitySecurityController extends Controller
{
    protected $securityController;

    public function __construct(SecurityController $securityController)
    {
        $this->securityController = $securityController;
    }

    /**
     * Get 2FA Status
     */
    public function get2FAStatus(Request $request)
    {
        return $this->securityController->get2FAStatus($request);
    }

    /**
     * Enable Two-Factor Authentication (Setup/Get QR Code)
     * Returns existing pending secret if available, preventing regeneration
     */
    public function enable2FA(Request $request)
    {
        \Log::info('Charity 2FA: Enable request', [
            'user_id' => $request->user()->id,
            'charity_id' => $request->user()->charity?->id
        ]);
        
        return $this->securityController->enable2FA($request);
    }

    /**
     * Verify and Activate 2FA
     */
    public function verify2FA(Request $request)
    {
        \Log::info('Charity 2FA: Verify request', [
            'user_id' => $request->user()->id,
            'charity_id' => $request->user()->charity?->id
        ]);
        
        return $this->securityController->verify2FA($request);
    }

    /**
     * Disable Two-Factor Authentication
     */
    public function disable2FA(Request $request)
    {
        \Log::info('Charity 2FA: Disable request', [
            'user_id' => $request->user()->id,
            'charity_id' => $request->user()->charity?->id
        ]);
        
        return $this->securityController->disable2FA($request);
    }
}
