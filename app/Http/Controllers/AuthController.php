<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Charity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Services\SecurityService;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Schema;
use App\Mail\EmailVerifiedMail;
use App\Mail\PasswordChangedMail;
use App\Mail\AccountDeactivatedMail;

class AuthController extends Controller
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }
    public function registerDonor(Request $r){
        try {
            $data = $r->validate([
                'name'=>'required|string|max:255',
                'email'=>'required|email|unique:users,email',
                'password'=>'required|min:6|confirmed',
                'phone'=>'nullable|string',
                'address'=>'nullable|string',
                'profile_image'=>'nullable|image|max:2048',
                // Required location fields
                'region'=>'required|string|max:255',
                'province'=>'required|string|max:255',
                'city'=>'required|string|max:255',
                'barangay'=>'required|string|max:255'
            ]);
            
            // Handle profile image upload
            $profileImagePath = null;
            if ($r->hasFile('profile_image')) {
                $profileImagePath = $r->file('profile_image')->store('profile_images', 'public');
            }
            
            $user = User::create([
                'name'=>$data['name'],
                'email'=>$data['email'],
                'password'=>Hash::make($data['password']),
                'phone'=>$data['phone'] ?? null,
                'address'=>$data['address'] ?? null,
                'profile_image'=>$profileImagePath,
                'region'=>$data['region'],
                'province'=>$data['province'],
                'city'=>$data['city'],
                'barangay'=>$data['barangay'],
                'role'=>'donor',
                'status'=>'active'
            ]);
            
            // Log successful registration
            $this->securityService->logAuthEvent('register', $user, [
                'role' => 'donor',
                'registration_method' => 'email'
            ]);
            
            // Notify admins about new user registration
            \App\Services\NotificationHelper::newUserRegistration($user);
            
            return response()->json([
                'message' => 'Registration successful',
                'user' => $user
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Register donor failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Server error creating account'], 500);
        }
    }

    public function registerCharityAdmin(Request $r){
        DB::beginTransaction();
        try {
            // Validate all fields - frontend sends different field names
            $validated = $r->validate([
                // Primary Contact - New structured fields
                'primary_first_name'=>'required|string|max:50',
                'primary_middle_initial'=>'nullable|string|max:1',
                'primary_last_name'=>'required|string|max:50',
                'primary_position'=>'nullable|string|max:100',
                'primary_email'=>'required|email|unique:users,email',
                'primary_phone'=>'required|string|max:15',
                'password'=>'required|string|min:6|confirmed',
                
                // Organization details
                'organization_name'=>'required|string|max:255',
                'registration_number'=>'nullable|string|max:255',
                'tax_id'=>'nullable|string|max:255',
                'mission_statement'=>'nullable|string|max:6000',
                'vision_statement'=>'nullable|string|max:6000',
                'description'=>'nullable|string|max:12000',
                'website'=>'nullable|string',
                
                // Location fields
                'street_address'=>'nullable|string',
                'barangay'=>'required|string|max:255',
                'city'=>'required|string|max:255',
                'province'=>'required|string|max:255',
                'region'=>'required|string|max:255',
                'full_address'=>'nullable|string',
                
                'nonprofit_category'=>'nullable|string',
                'legal_trading_name'=>'nullable|string',
                'accept_terms'=>'nullable',
                'confirm_truthfulness'=>'nullable',
                
                // Files (optional)
                'logo'=>'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
                'cover_image'=>'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
                'documents'=>'nullable',
                'doc_types'=>'nullable'
            ]);
            
            // Log file upload attempts for debugging
            Log::info('Charity registration file uploads', [
                'has_logo' => $r->hasFile('logo'),
                'has_cover' => $r->hasFile('cover_image'),
                'logo_size' => $r->hasFile('logo') ? $r->file('logo')->getSize() : 0,
                'cover_size' => $r->hasFile('cover_image') ? $r->file('cover_image')->getSize() : 0,
            ]);
            
            // Create user account with provided password
            // Combine first, middle initial, and last name
            $fullName = trim(implode(' ', array_filter([
                $validated['primary_first_name'],
                $validated['primary_middle_initial'] ?? null,
                $validated['primary_last_name']
            ])));
            
            $user = User::create([
                'name'=>$fullName,
                'email'=>$validated['primary_email'],
                'password'=>Hash::make($validated['password']),
                'phone'=>$validated['primary_phone'],
                'role'=>'charity_admin',
                'status'=>'active'
            ]);
            
            // Handle logo upload
            $logoPath = null;
            if ($r->hasFile('logo')) {
                $logoPath = $r->file('logo')->store('charity_logos', 'public');
                Log::info('Logo uploaded successfully', ['path' => $logoPath]);
            }
            
            // Handle cover image upload
            $coverPath = null;
            if ($r->hasFile('cover_image')) {
                $coverPath = $r->file('cover_image')->store('charity_covers', 'public');
                Log::info('Cover image uploaded successfully', ['path' => $coverPath]);
            }
            
            // Create charity organization with all fields
            $charity = Charity::create([
                'owner_id'=>$user->id,
                'name'=>$validated['organization_name'],
                'legal_trading_name'=>$validated['legal_trading_name'] ?? null,
                'reg_no'=>$validated['registration_number'] ?? null,
                'tax_id'=>$validated['tax_id'] ?? null,
                'mission'=>$validated['mission_statement'] ?? null,
                'vision'=>$validated['vision_statement'] ?? null,
                'description'=>$validated['description'] ?? null,
                'website'=>$validated['website'] ?? null,
                
                // Primary contact fields
                'primary_first_name'=>$validated['primary_first_name'],
                'primary_middle_initial'=>$validated['primary_middle_initial'] ?? null,
                'primary_last_name'=>$validated['primary_last_name'],
                'primary_position'=>$validated['primary_position'] ?? null,
                'primary_email'=>$validated['primary_email'],
                'primary_phone'=>$validated['primary_phone'],
                
                // Location fields
                'street_address'=>$validated['street_address'] ?? null,
                'barangay'=>$validated['barangay'] ?? null,
                'city'=>$validated['city'] ?? null,
                'province'=>$validated['province'] ?? null,
                'region'=>$validated['region'] ?? null,
                'full_address'=>$validated['full_address'] ?? null,
                
                'category'=>$validated['nonprofit_category'] ?? null,
                'logo_path'=>$logoPath,
                'cover_image'=>$coverPath,
                'verification_status'=>'pending'
            ]);
            
            // Handle document uploads
            if ($r->hasFile('documents')) {
                // Map frontend doc types to database enum values
                $docTypeMap = [
                    'registration_cert' => 'registration',
                    'tax_registration' => 'tax',
                    'financial_statement' => 'audit',
                    'representative_id' => 'other',
                    'additional_docs' => 'other'
                ];
                
                foreach ($r->file('documents') as $index => $file) {
                    $frontendDocType = $r->input("doc_types.{$index}", 'other');
                    $docType = $docTypeMap[$frontendDocType] ?? 'other';
                    $path = $file->store('charity_docs', 'public');
                    $hash = hash_file('sha256', $file->getRealPath());
                    
                    $charity->documents()->create([
                        'doc_type'=>$docType,
                        'file_path'=>$path,
                        'sha256'=>$hash,
                        'uploaded_by'=>$user->id
                    ]);
                }
            }
            
            // Log successful charity registration
            $this->securityService->logAuthEvent('register', $user, [
                'role' => 'charity_admin',
                'charity_id' => $charity->id,
                'charity_name' => $charity->name,
                'registration_method' => 'email'
            ]);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Registration successful. Your charity is pending verification.',
                'user' => $user,
                'charity' => $charity->load('documents')
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Charity registration validation failed', [
                'errors' => $e->errors(),
                'input' => $r->except(['password', 'password_confirmation'])
            ]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Register charity admin failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $r->except(['password', 'password_confirmation'])
            ]);
            return response()->json([
                'message' => 'Server error creating account',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $r){
        $data = $r->validate([
            'email'=>'required|email',
            'password'=>'required',
            'two_factor_code' => 'nullable|string|min:6|max:9' // Allow both TOTP (6) and recovery codes (9)
        ]);
        
        $user = User::where('email',$data['email'])->first();
        
        // Check if user exists and password is correct
        if(!$user || !Hash::check($data['password'], $user->password)){
            // Track failed login attempt
            if ($user) {
                $this->trackFailedLogin($data['email'], $r->ip(), $r->userAgent());
            }
            
            // Log failed login attempt
            $this->securityService->logFailedLogin($data['email'], $r->ip(), 'invalid_credentials');
            return response()->json(['message'=>'Invalid credentials'], 401);
        }
        
        // Check if account is locked
        $lockStatus = $this->securityService->isAccountLocked($user);
        if ($lockStatus['locked']) {
            $remainingTime = $this->securityService->getRemainingLockTime($user);
            return response()->json([
                'message' => "🚫 Too many failed login attempts. Please try again in {$remainingTime}.",
                'locked_until' => $lockStatus['locked_until'],
                'error_type' => 'account_locked'
            ], 429);
        }

        // Block login if user is suspended
        if ($user->status === 'suspended' && $user->suspended_until && now()->lt($user->suspended_until)) {
            \Log::info('Suspended user login attempt', ['user_id' => $user->id, 'email' => $user->email, 'suspended_until' => $user->suspended_until]);
            
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

        // Block login if charity admin's organization is deactivated
        if ($user->role === 'charity_admin' && $user->charity && ($user->charity->status ?? 'active') === 'inactive') {
            \Log::info('Inactive charity login attempt', ['user_id' => $user->id, 'email' => $user->email, 'charity_id' => $user->charity->id]);

            // Create charity reactivation request if none pending
            $existingCharityReq = \App\Models\CharityReactivationRequest::where('charity_id', $user->charity->id)
                ->where('status', 'pending')
                ->first();

            if (!$existingCharityReq) {
                $req = \App\Models\CharityReactivationRequest::create([
                    'charity_id' => $user->charity->id,
                    'email' => $user->email,
                    'requested_at' => now(),
                    'status' => 'pending',
                ]);
                \Log::info('Charity reactivation request created', ['request_id' => $req->id]);

                // Notify admins via NotificationHelper
                \App\Services\NotificationHelper::charityReactivationRequest($user->charity);
            } else {
                \Log::info('Existing charity reactivation request found', ['request_id' => $existingCharityReq->id]);
            }

            return response()->json([
                'message' => 'Your account is deactivated. A reactivation request has been sent to the admin. You will receive an email once approved.',
                'status' => 'inactive',
                'requires_admin_approval' => true,
                'scope' => 'charity',
            ], 403);
        }

        // Handle inactive (deactivated) user accounts
        if($user->status === 'inactive'){
            \Log::info('Inactive user login attempt', ['user_id' => $user->id, 'email' => $user->email]);
            
            // Create reactivation request
            $existingRequest = \App\Models\ReactivationRequest::where('user_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if (!$existingRequest) {
                \Log::info('Creating new reactivation request', ['user_id' => $user->id]);
                
                $reactivationRequest = \App\Models\ReactivationRequest::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'requested_at' => now(),
                    'status' => 'pending'
                ]);

                \Log::info('Reactivation request created', ['request_id' => $reactivationRequest->id]);

                // Notify admins about reactivation request
                \Log::info('Notifying admins about reactivation request');
                \App\Services\NotificationHelper::reactivationRequest($user);
                \Log::info('All admins notified');
            } else {
                \Log::info('Existing reactivation request found', ['request_id' => $existingRequest->id]);
            }

            return response()->json([
                'message' => 'Your account is deactivated. A reactivation request has been sent to the admin. You will receive an email once approved.',
                'status' => 'inactive',
                'requires_admin_approval' => true
            ], 403);
        }

        // Handle suspended accounts (catch-all for any other non-active status)
        if($user->status !== 'active'){
            $this->securityService->logFailedLogin($data['email'], $r->ip(), 'account_suspended');
            return response()->json(['message'=>'Your account has been suspended. Please contact support.'], 403);
        }

        // Initialize 2FA tracking variables (used later in response)
        $usedRecoveryCode = false;
        $remainingRecoveryCodes = 0;

        // Check if 2FA is enabled
        if ($user->two_factor_enabled) {
            // If no 2FA code provided, request it
            if (!isset($data['two_factor_code'])) {
                \Log::info('Login: 2FA required for user', ['user_id' => $user->id, 'email' => $user->email]);
                
                return response()->json([
                    'requires_2fa' => true,
                    'message' => 'Two-factor authentication required',
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->name,
                        'role' => $user->role,
                    ]
                ], 200);
            }

            $inputCode = $data['two_factor_code'];
            $isRecoveryCodeFormat = preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/i', $inputCode);
            
            // Verify TOTP code first (allow 2 windows = 60 seconds)
            $google2fa = new \PragmaRX\Google2FA\Google2FA();
            
            try {
                $secret = decrypt($user->two_factor_secret);
            } catch (\Exception $e) {
                \Log::error('Login: Failed to decrypt 2FA secret', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                return response()->json(['message'=>'2FA configuration error. Please contact support.'], 500);
            }
            
            // If it looks like a recovery code, skip TOTP verification
            if (!$isRecoveryCodeFormat) {
                $valid = $google2fa->verifyKey($secret, $inputCode, 2);
            } else {
                $valid = false; // Force recovery code check
            }
            
            if (!$valid) {
                // Check recovery codes
                try {
                    $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
                    
                    if (!is_array($recoveryCodes)) {
                        $recoveryCodes = [];
                    }
                    
                    // Normalize input for comparison (uppercase, no spaces)
                    $normalizedInput = strtoupper(trim($inputCode));
                    
                    if (in_array($normalizedInput, $recoveryCodes)) {
                        // Valid recovery code - remove it
                        \Log::info('Login: Recovery code used', [
                            'user_id' => $user->id,
                            'code' => substr($normalizedInput, 0, 4) . '****'
                        ]);
                        
                        $recoveryCodes = array_diff($recoveryCodes, [$normalizedInput]);
                        $remainingRecoveryCodes = count($recoveryCodes);
                        $user->two_factor_recovery_codes = encrypt(json_encode(array_values($recoveryCodes)));
                        $user->save();
                        
                        $usedRecoveryCode = true;
                        
                        // Warn if running low
                        if ($remainingRecoveryCodes <= 3 && $remainingRecoveryCodes > 0) {
                            \Log::warning('Login: Low recovery codes remaining', [
                                'user_id' => $user->id,
                                'remaining' => $remainingRecoveryCodes
                            ]);
                        } elseif ($remainingRecoveryCodes === 0) {
                            \Log::warning('Login: All recovery codes used', ['user_id' => $user->id]);
                        }
                    } else {
                        // Invalid code and not a valid recovery code
                        \Log::warning('Login: Invalid 2FA code', [
                            'user_id' => $user->id,
                            'is_recovery_format' => $isRecoveryCodeFormat
                        ]);
                        $this->securityService->logFailedLogin($data['email'], $r->ip(), 'invalid_2fa_code');
                        return response()->json([
                            'message' => $isRecoveryCodeFormat 
                                ? 'Invalid or already used recovery code' 
                                : 'Invalid two-factor code'
                        ], 401);
                    }
                } catch (\Exception $e) {
                    // Recovery code check failed
                    \Log::warning('Login: Invalid 2FA code (recovery codes unavailable)', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                    $this->securityService->logFailedLogin($data['email'], $r->ip(), 'invalid_2fa_code');
                    return response()->json(['message'=>'Invalid two-factor code'], 401);
                }
            } else {
                \Log::info('Login: 2FA TOTP verification successful', ['user_id' => $user->id]);
            }
        }

        // Reset failed login attempts on successful login
        $this->securityService->resetFailedAttempts($user);
        
        // Clear old failed login records
        \App\Models\FailedLogin::where('email', $data['email'])
            ->where('ip_address', $r->ip())
            ->delete();

        // Log successful login
        $this->securityService->logAuthEvent('login', $user, [
            'login_method' => $user->two_factor_enabled ? '2fa' : 'password'
        ]);

        // Check for suspicious login patterns
        $this->securityService->checkSuspiciousLogin($user, $r->ip());

        $token = $user->createToken('auth')->plainTextToken;

        // Load charity data for charity admins
        $responseUser = $user;
        if ($user->role === 'charity_admin' && $user->charity) {
            $responseUser = $user->load('charity');
        }

        // Build response
        $response = [
            'token' => $token,
            'user' => $responseUser
        ];
        
        // Add recovery code warning if used
        if (isset($usedRecoveryCode) && $usedRecoveryCode) {
            $response['used_recovery_code'] = true;
            $response['remaining_recovery_codes'] = $remainingRecoveryCodes;
            
            if ($remainingRecoveryCodes <= 3) {
                $response['warning'] = $remainingRecoveryCodes === 0
                    ? 'You have used all your recovery codes. Please generate new codes immediately.'
                    : "You have only {$remainingRecoveryCodes} recovery codes remaining. Consider generating new codes soon.";
            }
        }

        return response()->json($response);
    }

    /**
     * Track failed login attempts and send alert after threshold
     */
    private function trackFailedLogin($email, $ipAddress, $userAgent)
    {
        // If migrations haven't created the table yet, skip gracefully to avoid 500s
        try {
            if (!Schema::hasTable('failed_logins')) {
                return; // Table not ready; do not block login flow
            }

            $failedLogin = \App\Models\FailedLogin::firstOrCreate(
                [
                    'email' => $email,
                    'ip_address' => $ipAddress,
                ],
                [
                    'user_agent' => $userAgent,
                    'attempts' => 0,
                    'last_attempt_at' => now(),
                ]
            );

            // Increment attempts
            $failedLogin->increment('attempts');
            $failedLogin->update(['last_attempt_at' => now()]);

            // Send alert after 5 failed attempts
            if ($failedLogin->attempts >= 5 && !$failedLogin->alert_sent) {
                $user = User::where('email', $email)->first();
                
                if ($user) {
                    // Send security alert email
                    \Mail::to($user->email)->queue(
                        new \App\Mail\Security\FailedLoginAlertMail($user, $failedLogin)
                    );

                    // Mark alert as sent
                    $failedLogin->update([
                        'alert_sent' => true,
                        'alert_sent_at' => now(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Never crash login due to tracking; log and continue
            Log::warning('Failed login tracking skipped', [
                'reason' => $e->getMessage(),
            ]);
        }
    }

    public function logout(Request $r){
        // Log logout activity
        $this->securityService->logAuthEvent('logout', $r->user(), [
            'logout_method' => 'manual'
        ]);
        
        $r->user()->currentAccessToken()->delete();
        return response()->json(['message'=>'Logged out']);
    }

    public function me(Request $r){
        $user = $r->user();
        
        // Load charity data for charity admins
        if ($user->role === 'charity_admin' && $user->charity) {
            $user = $user->load('charity');
        }
        
        return response()->json($user);
    }

    public function updateProfile(Request $r){
        $user = $r->user();

        // Enhanced validation based on user role
        $validationRules = [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string|max:500',
        ];

        // Add role-specific fields
        if ($user->role === 'donor') {
            $validationRules['display_name'] = 'sometimes|string|max:255';
            $validationRules['location'] = 'sometimes|nullable|string|max:255';
            $validationRules['bio'] = 'sometimes|nullable|string|max:500';
            $validationRules['interests'] = 'sometimes|nullable|json';
            $validationRules['profile_image'] = 'sometimes|image|mimes:jpeg,png,jpg|max:2048';
        }

        // Add profile image support for admins
        if ($user->role === 'admin') {
            $validationRules['profile_image'] = 'sometimes|image|mimes:jpeg,png,jpg|max:2048';
        }

        if ($user->role === 'charity_admin') {
            $validationRules['contact_person_name'] = 'sometimes|string|max:255';
            $validationRules['contact_email'] = 'sometimes|email|unique:users,email,' . $user->id;
            $validationRules['contact_phone'] = 'sometimes|nullable|string|max:20';
            $validationRules['organization_name'] = 'sometimes|string|max:255';
            $validationRules['registration_number'] = 'sometimes|nullable|string|max:255';
            $validationRules['tax_id'] = 'sometimes|nullable|string|max:255';
            $validationRules['mission_statement'] = 'sometimes|nullable|string|max:1000';
            $validationRules['vision_statement'] = 'sometimes|nullable|string|max:1000';
            $validationRules['description'] = 'sometimes|nullable|string|max:1000';
            $validationRules['website'] = 'sometimes|nullable|string|max:255';
            $validationRules['address'] = 'sometimes|nullable|string|max:500';
            $validationRules['region'] = 'sometimes|nullable|string|max:255';
            $validationRules['municipality'] = 'sometimes|nullable|string|max:255';
            $validationRules['nonprofit_category'] = 'sometimes|nullable|string|max:255';
            $validationRules['legal_trading_name'] = 'sometimes|nullable|string|max:255';
            $validationRules['logo'] = 'sometimes|image|mimes:jpeg,png,jpg|max:2048';
            $validationRules['cover_image'] = 'sometimes|image|mimes:jpeg,png,jpg|max:2048';
        }

        $validatedData = $r->validate($validationRules);

        // Handle interests field for donors (comes as JSON string from frontend)
        if ($user->role === 'donor' && isset($validatedData['interests'])) {
            if (is_string($validatedData['interests'])) {
                $validatedData['interests'] = json_decode($validatedData['interests'], true);
            }
        }

        // Handle profile image upload for donors and admins
        if ($r->hasFile('profile_image') && ($user->role === 'donor' || $user->role === 'admin')) {
            // Delete old profile image if exists
            if ($user->profile_image) {
                \Storage::disk('public')->delete($user->profile_image);
            }
            $validatedData['profile_image'] = $r->file('profile_image')->store('profile_images', 'public');
        }

        // Handle charity logo upload for charity admins
        if ($r->hasFile('logo') && $user->role === 'charity_admin') {
            // Delete old logo if exists
            if ($user->charity && $user->charity->logo_path) {
                \Storage::disk('public')->delete($user->charity->logo_path);
            }
            $validatedData['logo_path'] = $r->file('logo')->store('charity_logos', 'public');
        }

        // Handle charity cover image upload for charity admins
        if ($r->hasFile('cover_image') && $user->role === 'charity_admin') {
            // Delete old cover image if exists
            if ($user->charity && $user->charity->cover_image) {
                \Storage::disk('public')->delete($user->charity->cover_image);
            }
            $validatedData['cover_image'] = $r->file('cover_image')->store('charity_covers', 'public');
        }

        // Update user profile
        $user->update($validatedData);

        // Update charity information if user is charity admin
        if ($user->role === 'charity_admin' && $user->charity) {
            $charityData = [];

            // Map form fields to charity model fields
            if (isset($validatedData['contact_person_name'])) {
                $charityData['contact_email'] = $validatedData['contact_email'] ?? $user->email;
                $charityData['contact_phone'] = $validatedData['contact_phone'] ?? null;
            }

            if (isset($validatedData['organization_name'])) {
                $charityData['name'] = $validatedData['organization_name'];
            }

            if (isset($validatedData['registration_number'])) {
                $charityData['reg_no'] = $validatedData['registration_number'];
            }

            if (isset($validatedData['tax_id'])) {
                $charityData['tax_id'] = $validatedData['tax_id'];
            }

            if (isset($validatedData['mission_statement'])) {
                $charityData['mission'] = $validatedData['mission_statement'];
            }

            if (isset($validatedData['description'])) {
                $charityData['vision'] = $validatedData['description'];
            }

            if (isset($validatedData['website'])) {
                $charityData['website'] = $validatedData['website'];
            }

            if (isset($validatedData['address'])) {
                $charityData['address'] = $validatedData['address'];
            }

            if (isset($validatedData['region'])) {
                $charityData['region'] = $validatedData['region'];
            }

            if (isset($validatedData['municipality'])) {
                $charityData['municipality'] = $validatedData['municipality'];
            }

            if (isset($validatedData['nonprofit_category'])) {
                $charityData['category'] = $validatedData['nonprofit_category'];
            }

            if (isset($validatedData['legal_trading_name'])) {
                $charityData['legal_trading_name'] = $validatedData['legal_trading_name'];
            }

            if (isset($validatedData['logo_path'])) {
                $charityData['logo_path'] = $validatedData['logo_path'];
            }

            if (isset($validatedData['cover_image'])) {
                $charityData['cover_image'] = $validatedData['cover_image'];
            }

            // Only update if there are charity fields to update
            if (!empty($charityData)) {
                $user->charity->update($charityData);
            }
        }

        // Log the profile update with detailed information
        ActivityLogService::logProfileUpdate($user->id, [
            'updated_fields' => array_keys($validatedData),
            'user_role' => $user->role,
            'is_donor' => $user->role === 'donor',
            'is_charity' => $user->role === 'charity_admin',
            'has_image_upload' => $r->hasFile('profile_image') || $r->hasFile('logo') || $r->hasFile('cover_image')
        ]);

        // Return updated user with charity data if applicable
        $responseData = $user->fresh(); // Get fresh data from database
        if ($user->charity) {
            $responseData = $user->load('charity');
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $responseData
        ]);
    }

    public function changePassword(Request $r){
        $user = $r->user();

        $data = $r->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        // Verify current password
        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        // Update password
        $user->update([
            'password' => Hash::make($data['new_password'])
        ]);

        // Send password changed confirmation email
        Mail::to($user->email)->queue(
            new PasswordChangedMail($user, $r->ip(), $r->userAgent())
        );

        // Log password change
        ActivityLogService::logPasswordChange($user->id);

        return response()->json(['message' => 'Password changed successfully']);
    }

    public function deactivateAccount(Request $r){
        $user = $r->user();

        $data = $r->validate([
            'password' => 'required|string',
            'reason' => 'nullable|string|max:500'
        ]);

        // Verify password before deactivation
        if (!Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Password is incorrect'], 422);
        }

        // Log account deactivation
        ActivityLogService::logAccountDeactivated($user->id);

        // Set status to inactive
        $user->update(['status' => 'inactive']);

        // Send account deactivation confirmation email
        Mail::to($user->email)->queue(new AccountDeactivatedMail($user));

        // Send notification to admins
        \App\Services\NotificationHelper::userDeactivated($user, $data['reason'] ?? null);

        return response()->json(['message' => 'Account deactivated successfully']);
    }

    public function deleteAccount(Request $r){
        $user = $r->user();

        $data = $r->validate([
            'password' => 'required|string',
            'reason' => 'nullable|string|max:500'
        ]);

        // Verify password before deletion
        if (!Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Password is incorrect'], 422);
        }

        // Log account deletion before deleting user
        ActivityLogService::logAccountDeleted($user->id);

        // Hard delete the user and all associated data
        $user->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }

    /**
     * Account Retrieval - Donor
     */
    public function retrieveDonorAccount(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'message' => 'required|string|max:1000',
        ]);

        // Check if user exists and is deactivated
        $user = User::where('email', $validated['email'])
            ->where('role', 'donor')
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'No donor account found with this email address'
            ], 404);
        }

        if ($user->status === 'active') {
            return response()->json([
                'message' => 'This account is already active'
            ], 422);
        }

        // Create retrieval request
        $retrievalRequest = \App\Models\AccountRetrievalRequest::create([
            'user_id' => $user->id,
            'email' => $validated['email'],
            'type' => 'donor',
            'message' => $validated['message'],
            'status' => 'pending',
        ]);

        // Send confirmation email
        \Mail::to($validated['email'])->queue(
            new \App\Mail\Security\AccountRetrievalRequestMail($retrievalRequest)
        );

        return response()->json([
            'success' => true,
            'message' => 'Account retrieval request submitted successfully. You will receive an email confirmation shortly.',
            'request_id' => $retrievalRequest->id,
        ], 201);
    }

    /**
     * Account Retrieval - Charity
     */
    public function retrieveCharityAccount(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'organization_name' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
        ]);

        // Check if user exists and is charity admin
        $user = User::where('email', $validated['email'])
            ->where('role', 'charity_admin')
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'No charity account found with this email address'
            ], 404);
        }

        if ($user->status === 'active') {
            return response()->json([
                'message' => 'This account is already active'
            ], 422);
        }

        // Create retrieval request
        $retrievalRequest = \App\Models\AccountRetrievalRequest::create([
            'user_id' => $user->id,
            'email' => $validated['email'],
            'type' => 'charity',
            'message' => $validated['message'],
            'status' => 'pending',
        ]);

        // Send confirmation email
        \Mail::to($validated['email'])->queue(
            new \App\Mail\Security\AccountRetrievalRequestMail($retrievalRequest)
        );

        return response()->json([
            'success' => true,
            'message' => 'Account retrieval request submitted successfully. You will receive an email confirmation shortly.',
            'request_id' => $retrievalRequest->id,
        ], 201);
    }

    /**
     * Reactivate Account
     */
    public function reactivateAccount(Request $request)
    {
        $user = $request->user();

        if ($user->status === 'active') {
            return response()->json([
                'message' => 'Account is already active'
            ], 422);
        }

        // Reactivate account
        $user->update(['status' => 'active']);

        // Log reactivation
        $this->securityService->logActivity($user, 'account_reactivated', [
            'reactivated_at' => now()->toISOString()
        ]);

        // Send confirmation email
        \Mail::to($user->email)->queue(
            new \App\Mail\Security\AccountReactivatedMail($user)
        );

        return response()->json([
            'success' => true,
            'message' => 'Account reactivated successfully'
        ]);
    }

    /**
     * Minimal donor registration - only name, email, password
     * Sends verification code email
     */
    public function registerMinimal(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            // Create user with unverified email
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'donor',
                'status' => 'active',
                'email_verified_at' => null, // Unverified
            ]);

            // Generate 6-digit code
            $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $token = \Str::random(60);
            $expiresAt = now()->addMinutes(5); // Changed from 15 to 5 minutes

            // Create verification record
            \App\Models\EmailVerification::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'code' => $code,
                'token' => $token,
                'expires_at' => $expiresAt,
                'attempts' => 0,
                'resend_count' => 0,
            ]);

            // Send verification email
            try {
                \Mail::to($user->email)->send(
                    new \App\Mail\VerifyEmailMail([
                        'name' => $user->name,
                        'email' => $user->email,
                        'code' => $code,
                        'token' => $token,
                        'expires_in' => 5,
                    ])
                );
            } catch (\Exception $mailError) {
                Log::error('Email sending failed during registration', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $mailError->getMessage(),
                ]);
                // Continue anyway - user is registered, they can resend
            }

            // Log registration
            try {
                $this->securityService->logAuthEvent('register', $user, [
                    'role' => 'donor',
                    'registration_method' => 'minimal',
                    'verification_sent' => true,
                ]);
            } catch (\Exception $logError) {
                // Log but don't fail registration
                Log::error('Failed to log auth event', ['error' => $logError->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Registration complete. Verification code sent to your email.',
                'email' => $user->email,
                'expires_in' => 5, // minutes
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Minimal registration failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Server error during registration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify email with code (primary method)
     */
    public function verifyEmailCode(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $verification = \App\Models\EmailVerification::where('email', $validated['email'])
            ->where('code', $validated['code'])
            ->first();

        if (!$verification) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code'
            ], 400);
        }

        // Check if expired
        if ($verification->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Verification code has expired. Please request a new one.',
                'expired' => true
            ], 400);
        }

        // Check max attempts
        if ($verification->hasMaxAttempts()) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum verification attempts reached. Please request a new code.',
                'max_attempts' => true
            ], 429);
        }

        // Verify the code
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            $verification->incrementAttempts();
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Mark email as verified
        $user->email_verified_at = now();
        $user->save();

        // Delete verification record
        $verification->delete();

        // Send email verified confirmation
        Mail::to($user->email)->queue(new EmailVerifiedMail($user));

        // Log verification
        $this->securityService->logAuthEvent('email_verified', $user, [
            'method' => 'code'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully!',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
            ]
        ]);
    }

    /**
     * Verify email with token (fallback link method)
     */
    public function verifyEmailToken(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
        ]);

        $verification = \App\Models\EmailVerification::where('email', $validated['email'])
            ->where('token', $validated['token'])
            ->first();

        if (!$verification) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification link'
            ], 400);
        }

        // Check if expired
        if ($verification->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Verification link has expired. Please request a new one.',
                'expired' => true
            ], 400);
        }

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Mark email as verified
        $user->email_verified_at = now();
        $user->save();

        // Delete verification record
        $verification->delete();

        // Log verification
        $this->securityService->logAuthEvent('email_verified', $user, [
            'method' => 'token'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully!',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
            ]
        ]);
    }

    /**
     * Resend verification code
     */
    public function resendVerificationCode(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email already verified'
            ], 400);
        }

        // Get existing verification
        $verification = \App\Models\EmailVerification::where('email', $validated['email'])->first();

        // Check resend limits (3 resends in 30 minutes)
        if ($verification) {
            // Check if created within 30 minutes
            if ($verification->created_at->gt(now()->subMinutes(30))) {
                if ($verification->hasMaxResends()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Maximum resend limit reached. Please try again in 30 minutes.',
                        'retry_after' => $verification->created_at->addMinutes(30)->diffInSeconds(now())
                    ], 429);
                }
            } else {
                // Reset resend count if 30 minutes have passed
                $verification->resend_count = 0;
            }

            // Increment resend count
            $verification->resend_count++;
        } else {
            // Create new verification if doesn't exist
            $verification = new \App\Models\EmailVerification();
            $verification->user_id = $user->id;
            $verification->email = $user->email;
            $verification->resend_count = 0;
        }

        // Generate new code and token
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $token = \Str::random(60);
        $expiresAt = now()->addMinutes(5); // Changed from 15 to 5 minutes

        $verification->code = $code;
        $verification->token = $token;
        $verification->expires_at = $expiresAt;
        $verification->attempts = 0; // Reset attempts
        $verification->save();

        // Send new verification email immediately
        \Mail::to($user->email)->send(
            new \App\Mail\VerifyEmailMail([
                'name' => $user->name,
                'email' => $user->email,
                'code' => $code,
                'token' => $token,
                'expires_in' => 5,
            ])
        );

        return response()->json([
            'success' => true,
            'message' => 'A new verification code has been sent to your email.',
            'resend_count' => $verification->resend_count,
            'remaining_resends' => 3 - $verification->resend_count,
            'expires_in' => 5,
        ]);
    }
}
