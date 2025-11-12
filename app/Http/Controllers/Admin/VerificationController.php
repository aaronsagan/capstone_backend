<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Charity;
use Illuminate\Http\Request;
use App\Services\NotificationService;
use App\Services\NotificationHelper;

class VerificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    public function index(){
        return Charity::with(['owner:id,name,email', 'documents'])
            ->where('verification_status','pending')
            ->latest()
            ->paginate(20);
    }

    public function getAllCharities(Request $request){
        try {
            $query = Charity::with([
                'owner:id,name,email',
                'documents' => function($q) {
                    $q->orderBy('created_at', 'desc');
                },
                'campaigns' => function($q) {
                    $q->select('id', 'charity_id', 'title', 'description', 'target_amount', 'status', 'start_date', 'end_date')
                      ->withCount('donations as donors');
                }
            ])
            ->withCount(['campaigns', 'donations', 'followers']);

        // Filter by status if provided
        if ($request->has('status') && $request->status && $request->status !== 'all') {
            $query->where('verification_status', $request->status);
        }

        // Search by name or email if provided
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('contact_email', 'like', "%{$searchTerm}%")
                  ->orWhere('reg_no', 'like', "%{$searchTerm}%");
            });
        }

        $charities = $query->latest()->paginate(20);

        // Transform data to include full URLs and computed fields
        $charities->getCollection()->transform(function ($charity) {
            // Add full URLs for images
            if ($charity->logo_path) {
                $charity->logo = url('storage/' . $charity->logo_path);
            }
            if ($charity->cover_image) {
                $charity->background_image = url('storage/' . $charity->cover_image);
            }

            // Transform campaigns data
            if ($charity->campaigns) {
                $charity->campaigns->transform(function($campaign) {
                    $campaign->goal = $campaign->target_amount;
                    $campaign->raised = $campaign->current_amount; // This is computed via accessor
                    return $campaign;
                });
            }

            return $charity;
        });

        return $charities;
        } catch (\Exception $e) {
            \Log::error('getAllCharities error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch charities',
                'error' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function getUsers(Request $request){
        $query = \App\Models\User::with([
            'donorProfile',
            'charity' => function($query) {
                $query->select('id', 'owner_id', 'name', 'verification_status', 'status', 'logo_path');
            }
        ])
        ->withCount([
            'donations as total_donations',
            'charityFollows as charities_supported'
        ])
        ->when($request->boolean('only_verified'), function($q) {
            $q->whereNotNull('email_verified_at');
        });

        // Filter by role if provided
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        // Filter by status if provided
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Search by name or email if provided
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        $users = $query->latest()->paginate(20);

        // Add computed fields for each user
        $users->getCollection()->transform(function ($user) {
            // For donors: add donation stats
            if ($user->role === 'donor') {
                $user->total_amount = $user->donations()->sum('amount');
                $user->campaigns_backed = $user->donations()->distinct('campaign_id')->count('campaign_id');
            }

            // For charity admins: add charity info
            if ($user->role === 'charity_admin' && $user->charity) {
                $user->charity_name = $user->charity->name;
                $user->charity_status = $user->charity->verification_status;
                $user->charity_logo = $user->charity->logo_path;
                $user->charity_account_status = $user->charity->status;
            }

            // Add profile picture URL
            if ($user->profile_image) {
                $user->profile_picture = url('storage/' . $user->profile_image);
            }

            return $user;
        });

        return $users;
    }

    public function activateUser(\Illuminate\Http\Request $r, \App\Models\User $user){
        $previousStatus = $user->status;
        $user->update(['status'=>'active']);
        
        // If user was inactive (deactivated), send reactivation email and notification
        if ($previousStatus === 'inactive') {
            // Send email to user immediately with logging
            try {
                \Log::info('Sending reactivation email', ['to' => $user->email]);
                \Mail::to($user->email)->send(
                    new \App\Mail\Security\AccountReactivatedMail($user)
                );
                \Log::info('Reactivation email sent', ['to' => $user->email]);
            } catch (\Throwable $e) {
                \Log::error('Failed to send reactivation email', [
                    'to' => $user->email,
                    'error' => $e->getMessage()
                ]);
            }

            // Send database notification
            \App\Services\NotificationHelper::accountReactivated($user);
            
            // Update any pending reactivation requests
            \App\Models\ReactivationRequest::where('user_id', $user->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'approved',
                    'reviewed_at' => now(),
                    'reviewed_by' => $r->user()->id,
                    'admin_notes' => 'Approved via user management'
                ]);
        }
        
        return response()->json(['message'=>'User activated']);
    }

    public function approve(Request $r, Charity $charity){
        $charity->update([
            'verification_status'=>'approved',
            'verified_at'=>now(),
            'verification_notes'=>$r->input('notes')
        ]);

        // Send notification to charity owner
        $this->notificationService->sendVerificationStatus($charity, 'approved');
        
        // Create in-app notification
        NotificationHelper::charityVerificationStatus($charity, 'approved');

        return response()->json(['message'=>'Approved']);
    }

    public function reject(Request $r, Charity $charity){
        $rejectionReason = $r->input('reason');
        $charity->update([
            'verification_status'=>'rejected',
            'rejection_reason' => $rejectionReason,
            'verification_notes'=>$r->input('notes')
        ]);

        // Send notification to charity owner
        $this->notificationService->sendVerificationStatus($charity, 'rejected');
        
        // Create in-app notification
        NotificationHelper::charityVerificationStatus($charity, 'rejected');

        return response()->json(['message'=>'Rejected']);
    }

    public function suspendUser(Request $r, \App\Models\User $user){
        $user->update(['status'=>'suspended']);
        return response()->json(['message'=>'User suspended']);
    }
    
    // Debug endpoint to check charity document statuses
    public function checkCharityDocuments($charityId)
    {
        $charity = Charity::with('documents')->findOrFail($charityId);
        
        $totalDocs = $charity->documents()->count();
        $approvedDocs = $charity->documents()->where('verification_status', 'approved')->count();
        $pendingDocs = $charity->documents()->where('verification_status', 'pending')->count();
        $rejectedDocs = $charity->documents()->where('verification_status', 'rejected')->count();
        
        return response()->json([
            'charity_name' => $charity->name,
            'charity_status' => $charity->verification_status,
            'document_summary' => [
                'total' => $totalDocs,
                'approved' => $approvedDocs,
                'pending' => $pendingDocs,
                'rejected' => $rejectedDocs,
            ],
            'documents' => $charity->documents->map(function($doc) {
                return [
                    'id' => $doc->id,
                    'type' => $doc->document_type ?? $doc->doc_type,
                    'status' => $doc->verification_status,
                    'verified_at' => $doc->verified_at,
                    'rejection_reason' => $doc->rejection_reason,
                ];
            }),
            'can_auto_approve' => (
                in_array($charity->verification_status, ['pending', 'rejected']) &&
                $totalDocs > 0 &&
                $approvedDocs === $totalDocs &&
                $pendingDocs === 0 &&
                $rejectedDocs === 0
            ),
        ]);
    }

    public function approveDocument(Request $request, $documentId)
    {
        $document = \App\Models\CharityDocument::findOrFail($documentId);
        $admin = $request->user();
        
        $document->update([
            'verification_status' => 'approved',
            'verified_at' => now(),
            'verified_by' => $admin->id,
            'rejection_reason' => null
        ]);

        // Check if ALL documents are approved, then auto-approve charity
        $charity = $document->charity->fresh(); // Get fresh charity data
        
        // Refresh document counts
        $totalDocs = $charity->documents()->count();
        $approvedDocs = $charity->documents()->where('verification_status', 'approved')->count();
        $pendingDocs = $charity->documents()->where('verification_status', 'pending')->count();
        $rejectedDocs = $charity->documents()->where('verification_status', 'rejected')->count();
        
        // Log for debugging
        \Log::info("Document Approval Check for Charity: {$charity->name}", [
            'charity_id' => $charity->id,
            'charity_status' => $charity->verification_status,
            'total_docs' => $totalDocs,
            'approved_docs' => $approvedDocs,
            'pending_docs' => $pendingDocs,
            'rejected_docs' => $rejectedDocs,
        ]);
        
        $charityAutoApproved = false;
        
        // Auto-approve charity if:
        // 1. Charity is currently pending OR rejected
        // 2. All documents are approved (no pending or rejected)
        // 3. At least one document exists
        $canAutoApprove = in_array($charity->verification_status, ['pending', 'rejected']) && 
            $totalDocs > 0 && 
            $approvedDocs === $totalDocs && 
            $pendingDocs === 0 && 
            $rejectedDocs === 0;
            
        \Log::info("Auto-Approval Check Result", [
            'charity_name' => $charity->name,
            'can_auto_approve' => $canAutoApprove,
            'status_check' => in_array($charity->verification_status, ['pending', 'rejected']),
            'has_docs' => $totalDocs > 0,
            'all_approved' => $approvedDocs === $totalDocs,
            'no_pending' => $pendingDocs === 0,
            'no_rejected' => $rejectedDocs === 0,
        ]);
        
        if ($canAutoApprove) {
            
            $wasRejected = $charity->verification_status === 'rejected';
            
            $charity->update([
                'verification_status' => 'approved',
                'verified_at' => now(),
                'status' => 'active', // Also activate the charity
                'verification_notes' => $wasRejected 
                    ? 'All documents re-verified and approved. Charity status changed from rejected to approved and activated.'
                    : 'All documents verified and approved. Charity automatically activated.'
            ]);
            
            \Log::info("✅ Charity Auto-Approved!", ['charity_name' => $charity->name]);
            
            $charityAutoApproved = true;
            
            // Send notification to charity owner
            $this->notificationService->sendVerificationStatus($charity, 'approved');
            
            // Create in-app notification
            NotificationHelper::charityVerificationStatus($charity, 'approved');
            
            // Log the auto-approval
            \App\Models\AdminActionLog::logAction(
                $admin->id,
                'charity_auto_approved',
                'Charity',
                $charity->id,
                [
                    'trigger' => 'all_documents_approved',
                    'total_documents' => $totalDocs,
                    'approved_documents' => $approvedDocs,
                ],
                "Charity '{$charity->name}' automatically approved - all {$totalDocs} documents verified"
            );
        }

        return response()->json([
            'message' => 'Document approved successfully',
            'document' => $document->fresh(),
            'charity_auto_approved' => $charityAutoApproved,
            'charity_status' => $charity->fresh()->verification_status,
            'document_stats' => [
                'total' => $totalDocs,
                'approved' => $approvedDocs,
                'pending' => $pendingDocs,
                'rejected' => $rejectedDocs,
            ]
        ]);
    }

    public function rejectDocument(Request $request, $documentId)
    {
        $request->validate([
            'reason' => 'required|string|max:1000'
        ]);

        $document = \App\Models\CharityDocument::findOrFail($documentId);
        
        $document->update([
            'verification_status' => 'rejected',
            'rejection_reason' => $request->reason,
            'verified_at' => now(),
            'verified_by' => $request->user()->id
        ]);

        // Handle charity verification status when document is rejected
        $charity = $document->charity;
        
        // If charity was approved, revert to pending since a document was rejected
        if ($charity->verification_status === 'approved') {
            $charity->update([
                'verification_status' => 'pending',
                'verified_at' => null,
                'verification_notes' => 'Document "' . $document->doc_type . '" was rejected. Please re-upload the required document.'
            ]);
        }
        
        // Notify charity owner about document rejection
        if ($charity->owner) {
            $this->notificationService->sendSystemAlert(
                $charity->owner,
                "Your document '{$document->doc_type}' for {$charity->name} has been rejected. Reason: {$request->reason}. Please upload a corrected version.",
                'warning'
            );
        }
        
        return response()->json([
            'message' => 'Document rejected',
            'document' => $document->fresh(),
            'charity_status_updated' => $charity->verification_status === 'pending'
        ]);
    }

    public function getCharityDetails($charityId)
    {
        $charity = Charity::with([
            'owner:id,name,email',
            'documents' => function($q) {
                $q->orderBy('created_at', 'desc');
            },
            'campaigns' => function($q) {
                $q->select('id', 'charity_id', 'title', 'description', 'target_amount', 'status', 'start_date', 'end_date')
                  ->withCount('donations as donors');
            }
        ])
        ->withCount(['campaigns', 'donations', 'followers'])
        ->findOrFail($charityId);

        // Add full URLs for images
        if ($charity->logo_path) {
            $charity->logo = url('storage/' . $charity->logo_path);
        }
        if ($charity->cover_image) {
            $charity->background_image = url('storage/' . $charity->cover_image);
        }

        // Transform campaigns data
        if ($charity->campaigns) {
            $charity->campaigns->transform(function($campaign) {
                $campaign->goal = $campaign->target_amount;
                $campaign->raised = $campaign->current_amount; // Computed via accessor
                return $campaign;
            });
        }

        return response()->json($charity);
    }

    // List charity reactivation requests
    public function getCharityReactivationRequests(Request $r)
    {
        $requests = \App\Models\CharityReactivationRequest::with('charity:id,name,owner_id,status')
            ->orderByDesc('created_at')
            ->paginate(20);
        return response()->json($requests);
    }

    // Approve charity reactivation
    public function approveCharityReactivation(Request $r, $id)
    {
        $req = \App\Models\CharityReactivationRequest::findOrFail($id);
        $charity = \App\Models\Charity::findOrFail($req->charity_id);

        $previousStatus = $charity->status;
        $charity->update(['status' => 'active']);

        // Update request
        $req->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $r->user()->id,
            'admin_notes' => 'Approved via admin panel',
        ]);

        // Notify owner in-app
        \App\Services\NotificationHelper::charityReactivated($charity);

        // Email owner with logging
        try {
            if ($charity->owner) {
                \Log::info('Sending charity reactivation email', ['to' => $charity->owner->email, 'charity' => $charity->id]);
                $fromAddress = config('mail.from.address');
                $fromName = config('mail.from.name');
                \Mail::send('emails.system-alert', [
                    'user_name' => $charity->owner->name,
                    'alert_message' => "Your charity '{$charity->name}' has been reactivated.",
                    'type' => 'success'
                ], function($mail) use ($charity, $fromAddress, $fromName) {
                    if ($fromAddress) {
                        $mail->from($fromAddress, $fromName ?: config('app.name'));
                    }
                    $mail->to($charity->owner->email)
                            ->subject('Charity Reactivated - ' . $charity->name);
                });
                \Log::info('Charity reactivation email sent', ['to' => $charity->owner->email]);
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to send charity reactivation email', ['error' => $e->getMessage(), 'charity' => $charity->id]);
        }

        return response()->json(['message' => 'Charity reactivated']);
    }

    // Reject charity reactivation
    public function rejectCharityReactivation(Request $r, $id)
    {
        $r->validate(['notes' => 'nullable|string|max:1000']);
        $req = \App\Models\CharityReactivationRequest::findOrFail($id);
        $charity = \App\Models\Charity::findOrFail($req->charity_id);

        $req->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $r->user()->id,
            'admin_notes' => $r->input('notes'),
        ]);

        // Notify owner in-app via system alert email (reuse template)
        try {
            if ($charity->owner) {
                \Mail::send('emails.system-alert', [
                    'user_name' => $charity->owner->name,
                    'alert_message' => "Your charity '{$charity->name}' reactivation request was rejected." . ($r->input('notes') ? " Notes: " . $r->input('notes') : ''),
                    'type' => 'warning'
                ], function($mail) use ($charity) {
                    $mail->to($charity->owner->email)
                            ->subject('Charity Reactivation Rejected - ' . $charity->name);
                });
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to send charity reactivation rejection email', ['error' => $e->getMessage(), 'charity' => $charity->id]);
        }

        return response()->json(['message' => 'Reactivation request rejected']);
    }

    // Directly activate a charity (admin action, without referencing a specific request)
    public function activateCharity(Request $r, Charity $charity)
    {
        $previousStatus = $charity->status;
        $charity->update(['status' => 'active']);

        if ($previousStatus === 'inactive') {
            // Approve any pending reactivation requests for this charity
            \App\Models\CharityReactivationRequest::where('charity_id', $charity->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'approved',
                    'reviewed_at' => now(),
                    'reviewed_by' => $r->user()->id,
                    'admin_notes' => 'Approved directly via Users admin panel',
                ]);

            // Notify owner in-app
            \App\Services\NotificationHelper::charityReactivated($charity);

            // Email owner
            try {
                if ($charity->owner) {
                    \Log::info('Sending charity reactivation email (direct activate)', ['to' => $charity->owner->email, 'charity' => $charity->id]);
                    $fromAddress = config('mail.from.address');
                    $fromName = config('mail.from.name');
                    \Mail::send('emails.system-alert', [
                        'user_name' => $charity->owner->name,
                        'alert_message' => "Your charity '{$charity->name}' has been reactivated.",
                        'type' => 'success'
                    ], function($mail) use ($charity, $fromAddress, $fromName) {
                        if ($fromAddress) {
                            $mail->from($fromAddress, $fromName ?: config('app.name'));
                        }
                        $mail->to($charity->owner->email)
                                ->subject('Charity Reactivated - ' . $charity->name);
                    });
                    \Log::info('Charity reactivation email sent (direct activate)', ['to' => $charity->owner->email]);
                }
            } catch (\Throwable $e) {
                \Log::error('Failed to send charity reactivation email (direct activate)', ['error' => $e->getMessage(), 'charity' => $charity->id]);
            }
        }

        return response()->json(['message' => 'Charity activated']);
    }
}
