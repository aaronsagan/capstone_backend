<?php

namespace App\Http\Controllers;

use App\Models\{Donation, Charity, Campaign, RefundRequest};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;
use App\Services\NotificationHelper;
use App\Services\SecurityService;
use App\Mail\{DonationConfirmationMail, NewDonationAlertMail, DonationExportMail, DonationStatementMail, RefundRequestMail, DonationVerifiedMail, DonationRejectedMail, DonationAcknowledgmentMail};
use App\Jobs\SendCampaignCompletedEmails;
use Barryvdh\DomPDF\Facade\Pdf;

class DonationController extends Controller
{
    protected $notificationService;
    protected $securityService;

    public function __construct(NotificationService $notificationService, SecurityService $securityService)
    {
        $this->notificationService = $notificationService;
        $this->securityService = $securityService;
    }
    // Donor creates pledge (one-time or recurring)
    public function store(Request $r){
        $data = $r->validate([
            'charity_id'=>'required|exists:charities,id',
            'campaign_id'=>'nullable|exists:campaigns,id',
            'amount'=>'required|numeric|min:1',
            'purpose'=>'nullable|in:general,project,emergency',
            'is_anonymous'=>'boolean',
            'is_recurring'=>'boolean',
            'recurring_type'=>'nullable|in:weekly,monthly,quarterly,yearly',
            'recurring_end_date'=>'nullable|date|after:today',
            'external_ref'=>'nullable|string|max:64'
        ]);

        // Always store donor_id even for anonymous donations
        // This allows donors to see their anonymous donations in their own history
        // The is_anonymous flag will be used to hide donor info in public views
        $donation = Donation::create([
            'donor_id' => $r->user()->id,
            'charity_id'=>$data['charity_id'],
            'campaign_id'=>$data['campaign_id'] ?? null,
            'amount'=>$data['amount'],
            'purpose'=>$data['purpose'] ?? 'general',
            'is_anonymous'=>$data['is_anonymous'] ?? false,
            'is_recurring'=>$data['is_recurring'] ?? false,
            'recurring_type'=>$data['recurring_type'] ?? null,
            'recurring_end_date'=>$data['recurring_end_date'] ?? null,
            'next_donation_date' => $this->calculateNextDonationDate($data['recurring_type'] ?? null),
            'status'=>'pending',
            'external_ref'=>$data['external_ref'] ?? null,
            'donated_at' => now(),
        ]);

        // If recurring, create the next donation
        if (($data['is_recurring'] ?? false) && ($data['recurring_type'] ?? null)) {
            $this->scheduleNextRecurringDonation($donation);
        }

        // Log donation activity - each donation creates individual log entry
        \App\Services\ActivityLogService::logDonationCreated(
            $r->user()->id,
            $donation->id,
            $donation->amount,
            $donation->campaign_id
        );

        // Send email notifications
        $this->sendDonationEmails($donation);

        // Create in-app notifications
        NotificationHelper::donationConfirmed($donation);
        NotificationHelper::donationReceived($donation);
        NotificationHelper::newDonationAdmin($donation); // Notify admins

        return response()->json($donation->load(['charity', 'campaign']), 201);
    }

    // Donor uploads proof (image/pdf)
    public function uploadProof(Request $r, Donation $donation){
        $user = $r->user();
        $isOwner = $donation->donor_id === $user->id || 
                   ($donation->donor_id === null && $donation->donor_email === $user->email);
        abort_unless($isOwner, 403);
        $r->validate(['file'=>'required|file|mimes:jpg,jpeg,png,pdf','proof_type'=>'nullable|string|max:50']);
        $path = $r->file('file')->store('proofs','public');
        $donation->update(['proof_path'=>$path,'proof_type'=>$r->input('proof_type')]);
        return $donation->fresh();
    }

    // Submit manual donation with proof (campaign-specific)
    public function submitManualDonation(Request $r, Campaign $campaign){
        $data = $r->validate([
            'donor_name'=>'required|string|max:255',
            'donor_email'=>'nullable|email|max:255',
            'amount'=>'required|numeric|min:1',
            'channel_used'=>'required|string|max:255',
            'reference_number'=>'required|string|max:255',
            'proof_image'=>'required|image|mimes:jpg,jpeg,png|max:2048',
            'message'=>'nullable|string|max:1000',
            'is_anonymous'=>'boolean',
        ]);

        // Check for duplicate reference number
        $existingDonation = Donation::where('reference_number', $data['reference_number'])
            ->with(['campaign', 'charity'])
            ->first();

        if ($existingDonation) {
            $donatedDate = $existingDonation->donated_at ? $existingDonation->donated_at->format('F d, Y \a\t h:i A') : 'Unknown';
            $targetName = $existingDonation->campaign 
                ? $existingDonation->campaign->title 
                : ($existingDonation->charity ? $existingDonation->charity->name : 'Unknown');
            
            return response()->json([
                'message' => 'Duplicate Reference Number Detected',
                'error' => 'This reference number has already been used for a previous donation.',
                'details' => [
                    'reference_number' => $data['reference_number'],
                    'previous_donation_date' => $donatedDate,
                    'previous_donation_to' => $targetName,
                    'previous_donation_amount' => '₱' . number_format($existingDonation->amount, 2),
                    'status' => $existingDonation->status
                ]
            ], 422);
        }

        // Store proof image
        $proofPath = $r->file('proof_image')->store('proofs','public');

        // Get donor ID if authenticated
        // Keep donor_id even for anonymous donations for history tracking
        $donorId = $r->user() ? $r->user()->id : null;

        // Create donation record
        $donation = Donation::create([
            'donor_id' => $donorId,
            'donor_name' => $data['donor_name'],
            'donor_email' => $data['donor_email'] ?? null,
            'charity_id' => $campaign->charity_id,
            'campaign_id' => $campaign->id,
            'amount' => $data['amount'],
            'channel_used' => $data['channel_used'],
            'reference_number' => $data['reference_number'],
            'proof_path' => $proofPath,
            'proof_type' => 'image',
            'message' => $data['message'] ?? null,
            'is_anonymous' => $data['is_anonymous'] ?? false,
            'purpose' => 'project',
            'status' => 'pending',
            'donated_at' => now(),
        ]);

        // Send email notifications
        $this->sendDonationEmails($donation);

        // Create in-app notifications
        NotificationHelper::donationConfirmed($donation);
        NotificationHelper::donationReceived($donation);
        NotificationHelper::newDonationAdmin($donation); // Notify admins

        return response()->json([
            'message' => 'Thank you! Your proof of donation has been submitted for review.',
            'donation' => $donation->load(['charity', 'campaign'])
        ], 201);
    }

    // Submit manual donation directly to charity (not campaign-specific)
    public function submitCharityDonation(Request $r, Charity $charity){
        $data = $r->validate([
            'donor_name'=>'required|string|max:255',
            'donor_email'=>'nullable|email|max:255',
            'amount'=>'required|numeric|min:1',
            'channel_used'=>'required|string|max:255',
            'reference_number'=>'required|string|max:255',
            'proof_image'=>'required|image|mimes:jpg,jpeg,png|max:2048',
            'message'=>'nullable|string|max:1000',
            'is_anonymous'=>'boolean',
        ]);

        // Check for duplicate reference number
        $existingDonation = Donation::where('reference_number', $data['reference_number'])
            ->with(['campaign', 'charity'])
            ->first();

        if ($existingDonation) {
            $donatedDate = $existingDonation->donated_at ? $existingDonation->donated_at->format('F d, Y \a\t h:i A') : 'Unknown';
            $targetName = $existingDonation->campaign 
                ? $existingDonation->campaign->title 
                : ($existingDonation->charity ? $existingDonation->charity->name : 'Unknown');
            
            return response()->json([
                'message' => 'Duplicate Reference Number Detected',
                'error' => 'This reference number has already been used for a previous donation.',
                'details' => [
                    'reference_number' => $data['reference_number'],
                    'previous_donation_date' => $donatedDate,
                    'previous_donation_to' => $targetName,
                    'previous_donation_amount' => '₱' . number_format($existingDonation->amount, 2),
                    'status' => $existingDonation->status
                ]
            ], 422);
        }

        // Store proof image
        $proofPath = $r->file('proof_image')->store('proofs','public');

        // Get donor ID if authenticated
        // Keep donor_id even for anonymous donations for history tracking
        $donorId = $r->user() ? $r->user()->id : null;

        // Create donation record for direct charity donation
        $donation = Donation::create([
            'donor_id' => $donorId,
            'donor_name' => $data['donor_name'],
            'donor_email' => $data['donor_email'] ?? null,
            'charity_id' => $charity->id,
            'campaign_id' => null, // Direct to charity, not a campaign
            'amount' => $data['amount'],
            'channel_used' => $data['channel_used'],
            'reference_number' => $data['reference_number'],
            'proof_path' => $proofPath,
            'proof_type' => 'image',
            'message' => $data['message'] ?? null,
            'is_anonymous' => $data['is_anonymous'] ?? false,
            'purpose' => 'general', // Direct charity donations are general
            'status' => 'pending',
            'donated_at' => now(),
        ]);

        // Send email notifications
        $this->sendDonationEmails($donation);

        return response()->json([
            'message' => 'Thank you! Your donation has been submitted for review.',
            'donation' => $donation->load('charity')
        ], 201);
    }

    // Charity inbox (see donations for their org)
    public function charityInbox(Request $r, Charity $charity){
        abort_unless($charity->owner_id === $r->user()->id, 403);
        return $charity->donations()
            ->with(['donor:id,name,email,profile_image', 'campaign:id,title,cover_image_path', 'charity:id,name,logo_path'])
            ->latest()
            ->paginate(20);
    }

    // Charity confirms or rejects donation
    public function confirm(Request $r, Donation $donation){
        abort_unless($donation->charity->owner_id === $r->user()->id, 403);
        $data = $r->validate(['status'=>'required|in:completed,rejected']);
        $donation->update([
            'status'=>$data['status'],
            'receipt_no'=> $data['status']==='completed' ? Str::upper(Str::random(10)) : null
        ]);

        // Send notifications
        if($data['status'] === 'completed') {
            $this->notificationService->sendDonationConfirmation($donation);
            // Notify donor that donation is verified
            NotificationHelper::donationVerified($donation);
        }

        return $donation->fresh();
    }

    // Donor: my donations
    public function myDonations(Request $r){
        $user = $r->user();
        
        // Check if we should include refunded donations (for history/transparency)
        $includeRefunded = $r->input('include_refunded', true); // Default: show all
        
        // Get donations where:
        // 1. donor_id matches (new donations, including new anonymous ones)
        // 2. OR donor_id is NULL and email matches (old anonymous donations)
        $donations = Donation::where(function($query) use ($user) {
                $query->where('donor_id', $user->id)
                      ->orWhere(function($q) use ($user) {
                          $q->whereNull('donor_id')
                            ->where('donor_email', $user->email);
                      });
            })
            ->when(!$includeRefunded, function($query) {
                // If not including refunded, filter them out
                $query->where('is_refunded', false);
            })
            ->with(['charity:id,name,logo_path', 'campaign:id,title,cover_image_path'])
            ->latest('donated_at')
            ->paginate(20);
        
        // Add a flag to each donation indicating it's the owner's view
        // This helps the frontend display anonymous donations properly
        $donations->getCollection()->transform(function ($donation) {
            $donation->is_own_donation = true;
            return $donation;
        });
        
        return $donations;
    }

    // Download receipt for a donation
    public function downloadReceipt(Request $r, Donation $donation){
        // Check if user owns this donation (by donor_id or by email for old anonymous donations)
        $user = $r->user();
        $isOwner = $donation->donor_id === $user->id || 
                   ($donation->donor_id === null && $donation->donor_email === $user->email);
        
        if (!$isOwner) {
            abort(403, 'Unauthorized');
        }

        // Check if donation is completed
        if ($donation->status !== 'completed') {
            return response()->json(['message' => 'Receipt not available for pending donations'], 422);
        }

        // Generate PDF receipt
        $pdfContent = $this->generateReceiptPDF($donation);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="donation-receipt-' . $donation->receipt_no . '.pdf"'
        ]);
    }

    private function generateReceiptPDF($donation){
        // Simple PDF generation using basic HTML to PDF conversion
        // In a production system, you'd use a proper PDF library like TCPDF or DomPDF

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Donation Receipt</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                .header { text-align: center; margin-bottom: 30px; }
                .receipt-info { margin: 20px 0; }
                .footer { margin-top: 50px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Donation Receipt</h1>
                <p>Thank you for your generous donation!</p>
            </div>

            <div class='receipt-info'>
                <p><strong>Receipt Number:</strong> {$donation->receipt_no}</p>
                <p><strong>Date:</strong> {$donation->donated_at->format('F j, Y')}</p>
                <p><strong>Charity:</strong> {$donation->charity->name}</p>
                <p><strong>Amount:</strong> ₱" . number_format($donation->amount, 2) . "</p>
                <p><strong>Purpose:</strong> " . ucfirst($donation->purpose) . "</p>
                " . ($donation->campaign ? "<p><strong>Campaign:</strong> {$donation->campaign->title}</p>" : "") . "
            </div>

            <div class='footer'>
                <p>This receipt is generated electronically and is valid without signature.</p>
                <p>Generated on: " . now()->format('F j, Y \a\t g:i A') . "</p>
            </div>
        </body>
        </html>
        ";

        // For now, return HTML - in production, convert to PDF
        // You would use a library like DomPDF here:
        // return PDF::loadHTML($html)->download('receipt.pdf');

        return $html;
    }

    // Get recurring donations that need processing
    public function processRecurringDonations(){
        $pendingRecurring = Donation::where('is_recurring', true)
            ->where('next_donation_date', '<=', now())
            ->where(function($q) {
                $q->whereNull('recurring_end_date')
                  ->orWhere('recurring_end_date', '>', now());
            })
            ->get();

        foreach($pendingRecurring as $donation){
            $this->processRecurringDonation($donation);
        }

        return response()->json(['processed' => $pendingRecurring->count()]);
    }

    private function calculateNextDonationDate($recurringType){
        if(!$recurringType) return null;

        $now = now();
        switch($recurringType){
            case 'weekly': return $now->addWeek();
            case 'monthly': return $now->addMonth();
            case 'quarterly': return $now->addMonths(3);
            case 'yearly': return $now->addYear();
            default: return null;
        }
    }

    private function scheduleNextRecurringDonation($donation){
        if(!$donation->is_recurring || !$donation->recurring_type) return;

        $nextDate = $this->calculateNextDonationDate($donation->recurring_type);
        if($nextDate && (!$donation->recurring_end_date || $nextDate <= $donation->recurring_end_date)){
            Donation::create([
                'donor_id' => $donation->donor_id,
                'charity_id' => $donation->charity_id,
                'campaign_id' => $donation->campaign_id,
                'amount' => $donation->amount,
                'purpose' => $donation->purpose,
                'is_anonymous' => $donation->is_anonymous,
                'is_recurring' => true,
                'recurring_type' => $donation->recurring_type,
                'recurring_end_date' => $donation->recurring_end_date,
                'next_donation_date' => $nextDate,
                'status' => 'scheduled',
                'parent_donation_id' => $donation->id,
            ]);
        }
    }

    private function processRecurringDonation($donation){
        // Mark as completed and create next one
        $donation->update(['status' => 'completed']);
        $this->scheduleNextRecurringDonation($donation);
    }

    /**
     * Update donation status with optional rejection reason
     */
    public function updateStatus(Request $request, Donation $donation)
    {
        // Check authorization
        abort_unless($donation->charity->owner_id === $request->user()->id, 403, 'Unauthorized');

        $data = $request->validate([
            'status' => 'required|in:pending,completed,rejected',
            'reason' => 'nullable|string|max:500',
        ]);

        // Update donation status
        $updateData = ['status' => $data['status']];

        // Generate receipt number if completed
        if ($data['status'] === 'completed') {
            $updateData['receipt_no'] = Str::upper(Str::random(10));
        }

        // Store rejection reason if rejected
        if ($data['status'] === 'rejected' && isset($data['reason'])) {
            $updateData['rejection_reason'] = $data['reason'];
        }

        $donation->update($updateData);

        // Send notifications
        if ($data['status'] === 'completed') {
            $this->notificationService->sendDonationConfirmation($donation);
            
            // Send donation verified email
            if ($donation->donor) {
                Mail::to($donation->donor->email)->queue(new DonationVerifiedMail($donation));
                
                // Send acknowledgment letter with PDF attachment
                Mail::to($donation->donor->email)->queue(new DonationAcknowledgmentMail($donation));
            }

            // In-app notification
            NotificationHelper::donationVerified($donation);

            // Check if campaign has reached its goal
            if ($donation->campaign) {
                $campaign = $donation->campaign->fresh();
                $totalRaised = $campaign->current_amount;
                
                if ($totalRaised >= $campaign->target_amount && $campaign->status !== 'completed') {
                    // Mark campaign as completed
                    $campaign->update(['status' => 'completed']);
                    
                    // Send completion emails to all donors
                    dispatch(new SendCampaignCompletedEmails($campaign));
                    
                    Log::info('Campaign reached goal - completion emails dispatched', [
                        'campaign_id' => $campaign->id,
                        'target' => $campaign->target_amount,
                        'raised' => $totalRaised,
                    ]);
                }
            }
        }

        // Send rejection email
        if ($data['status'] === 'rejected') {
            if ($donation->donor) {
                $reason = $data['reason'] ?? 'Invalid or unclear proof of payment';
                Mail::to($donation->donor->email)->queue(new DonationRejectedMail($donation, $reason));
            }
        }

        return response()->json($donation->fresh()->load(['donor', 'charity', 'campaign']));
    }

    /**
     * Send donation confirmation and alert emails
     */
    private function sendDonationEmails(Donation $donation)
    {
        try {
            // Load relationships
            $donation->load(['donor', 'charity', 'campaign']);

            // Send confirmation email to donor
            if ($donation->donor) {
                Mail::to($donation->donor->email)->queue(new DonationConfirmationMail($donation));
                Log::info('Donation confirmation email queued', [
                    'donation_id' => $donation->id,
                    'donor_email' => $donation->donor->email
                ]);
            } elseif ($donation->donor_email) {
                Mail::to($donation->donor_email)->queue(new DonationConfirmationMail($donation));
                Log::info('Donation confirmation email queued', [
                    'donation_id' => $donation->id,
                    'donor_email' => $donation->donor_email
                ]);
            }

            // Send alert email to charity admin
            if ($donation->charity && $donation->charity->owner) {
                Mail::to($donation->charity->owner->email)->queue(new NewDonationAlertMail($donation));
                Log::info('New donation alert email queued', [
                    'donation_id' => $donation->id,
                    'charity_email' => $donation->charity->owner->email
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to queue donation emails', [
                'donation_id' => $donation->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Request refund for a donation
     */
    public function requestRefund(Request $request, $donationId)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
            'proof_image' => 'nullable|image|mimes:jpg,jpeg,png,pdf|max:5120',
            'message' => 'nullable|string|max:2000',
        ]);

        $donation = Donation::with(['charity', 'campaign'])->findOrFail($donationId);

        // Check if user owns this donation
        $user = $request->user();
        $isOwner = $donation->donor_id === $user->id || 
                   ($donation->donor_id === null && $donation->donor_email === $user->email);

        if (!$isOwner) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only request refunds for your own donations.'
            ], 403);
        }

        // Check if donation is refundable (within 7 days as per requirements)
        if ($donation->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Only completed donations can be refunded.'
            ], 422);
        }

        // Check 7-day limit from donation date (donated_at, not created_at)
        $donationDate = $donation->donated_at ?? $donation->created_at;
        $daysSince = $donationDate->diffInDays(now());
        
        if ($daysSince > 7) {
            return response()->json([
                'success' => false,
                'message' => 'Refund window has expired. Refunds are only allowed within 7 days of donation.',
                'days_since_donation' => $daysSince
            ], 422);
        }

        // Check if campaign has ended or completed
        if ($donation->campaign) {
            $campaign = $donation->campaign;
            $isEnded = $campaign->end_date && now()->isAfter($campaign->end_date);
            $isCompleted = $campaign->status === 'completed';
            
            if ($isEnded || $isCompleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refunds are not allowed for campaigns that have ended or completed.'
                ], 422);
            }
        }

        // Check if refund already requested (only one pending request per donation)
        $existingRequest = RefundRequest::where('donation_id', $donation->id)
            ->where('status', 'pending')
            ->first();
            
        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'A refund request is already pending for this donation.'
            ], 422);
        }

        // Store proof image if provided
        $proofPath = null;
        if ($request->hasFile('proof_image')) {
            $proofPath = $request->file('proof_image')->store('refund_proofs', 'public');
        }

        // Create refund request
        $refundRequest = RefundRequest::create([
            'donation_id' => $donation->id,
            'user_id' => $user->id,
            'charity_id' => $donation->charity_id,
            'reason' => $validated['reason'],
            'message' => $validated['message'] ?? null,
            'proof_path' => $proofPath,
            'status' => 'pending',
            'refund_amount' => $donation->amount,
        ]);

        // Load relationships for emails
        $refundRequest->load(['donation.charity', 'donation.campaign', 'user']);

        // Log the refund request
        $this->securityService->logActivity($user, 'refund_requested', [
            'refund_request_id' => $refundRequest->id,
            'donation_id' => $donation->id,
            'charity_id' => $donation->charity_id,
            'amount' => $donation->amount,
        ]);

        // Send refund request emails to both donor and charity
        try {
            // Email to donor (confirmation)
            Mail::to($user->email)->queue(new RefundRequestMail($user, $donation, $refundRequest, 'donor'));
            
            // Email to charity admin (notification)
            if ($donation->charity && $donation->charity->owner) {
                Mail::to($donation->charity->owner->email)->queue(
                    new RefundRequestMail($donation->charity->owner, $donation, $refundRequest, 'charity')
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send refund request emails', [
                'refund_request_id' => $refundRequest->id,
                'error' => $e->getMessage(),
            ]);
            // Don't fail the request if email fails
        }

        // Create in-app notifications
        NotificationHelper::refundRequestStatus($refundRequest, 'pending');
        NotificationHelper::refundRequestReceived($refundRequest);

        return response()->json([
            'success' => true,
            'message' => 'Refund request submitted successfully. The charity will review your request.',
            'refund_request' => $refundRequest,
            'days_remaining' => 7 - $daysSince,
        ], 201);
    }

    /**
     * Get donor's refund requests
     */
    public function getDonorRefunds(Request $request)
    {
        $user = $request->user();

        $refunds = RefundRequest::with(['donation.charity', 'donation.campaign', 'reviewer'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'refunds' => $refunds
        ]);
    }

    /**
     * Export donation history
     */
    public function exportDonations(Request $request)
    {
        $format = $request->get('format', 'csv');
        
        if (!in_array($format, ['csv', 'pdf'])) {
            return response()->json(['message' => 'Invalid format'], 422);
        }

        $user = $request->user();

        // Get user's donations
        $donations = Donation::where(function($query) use ($user) {
                $query->where('donor_id', $user->id)
                      ->orWhere(function($q) use ($user) {
                          $q->whereNull('donor_id')
                            ->where('donor_email', $user->email);
                      });
            })
            ->with(['charity', 'campaign'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($format === 'csv') {
            $csvData = $this->generateCSV($donations);
            
            // TODO: Send email with download link (temporarily disabled)
            // Mail::to($user->email)->queue(new DonationExportMail($user, $format));
            
            return response($csvData, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="donation-history.csv"',
            ]);
        } else {
            $pdfData = $this->generatePDF($donations, $user);
            
            // TODO: Send email with download link (temporarily disabled)
            // Mail::to($user->email)->queue(new DonationExportMail($user, $format));
            
            return response($pdfData, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="donation-history.pdf"',
            ]);
        }
    }

    /**
     * Generate annual donation statement
     */
    public function annualStatement(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $user = $request->user();

        // Get donations for the specified year
        $donations = Donation::where(function($query) use ($user) {
                $query->where('donor_id', $user->id)
                      ->orWhere(function($q) use ($user) {
                          $q->whereNull('donor_id')
                            ->where('donor_email', $user->email);
                      });
            })
            ->whereYear('created_at', $year)
            ->where('status', 'completed')
            ->with(['charity', 'campaign'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalAmount = $donations->sum('amount');
        $totalDonations = $donations->count();
        $charitiesSupported = $donations->pluck('charity_id')->unique()->count();

        // Generate PDF
        $pdfData = $this->generateStatementPDF($donations, $user, $year, [
            'total_amount' => $totalAmount,
            'total_donations' => $totalDonations,
            'charities_supported' => $charitiesSupported,
        ]);

        // Send email with statement
        Mail::to($user->email)->queue(new DonationStatementMail($user, $year, $pdfData));

        return response($pdfData, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"donation-statement-{$year}.pdf\"",
        ]);
    }

    /**
     * Generate CSV export
     */
    private function generateCSV($donations)
    {
        $csv = "Date,Charity,Campaign,Amount,Status,Receipt No\n";
        
        foreach ($donations as $donation) {
            $csv .= sprintf(
                "%s,%s,%s,%.2f,%s,%s\n",
                $donation->created_at->format('Y-m-d'),
                str_replace(',', ' ', $donation->charity->name ?? 'N/A'),
                str_replace(',', ' ', $donation->campaign->title ?? 'General'),
                $donation->amount,
                $donation->status,
                $donation->receipt_no ?? 'N/A'
            );
        }
        
        return $csv;
    }

    /**
     * Generate PDF export
     */
    private function generatePDF($donations, $user)
    {
        $html = "<!DOCTYPE html><html><head><title>Donation History</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            .header { text-align: center; margin-bottom: 30px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #667eea; color: white; }
            .total { font-weight: bold; text-align: right; }
        </style></head><body>
        <div class='header'>
            <h1>Donation History</h1>
            <p>{$user->name}</p>
            <p>" . date('F j, Y') . "</p>
        </div>
        <table>
            <tr><th>Date</th><th>Charity</th><th>Campaign</th><th>Amount</th><th>Status</th></tr>";
        
        $total = 0;
        foreach ($donations as $donation) {
            $html .= sprintf(
                "<tr><td>%s</td><td>%s</td><td>%s</td><td>PHP %.2f</td><td>%s</td></tr>",
                $donation->created_at->format('M d, Y'),
                $donation->charity->name ?? 'N/A',
                $donation->campaign->title ?? 'General',
                $donation->amount,
                ucfirst($donation->status)
            );
            $total += $donation->amount;
        }
        
        $html .= sprintf(
            "<tr class='total'><td colspan='3'>Total</td><td>PHP %.2f</td><td></td></tr>",
            $total
        );
        
        $html .= "</table></body></html>";
        
        // Generate PDF using dompdf
        $pdf = Pdf::loadHTML($html);
        return $pdf->output();
    }

    /**
     * Generate statement PDF
     */
    private function generateStatementPDF($donations, $user, $year, $summary)
    {
        $html = "<!DOCTYPE html><html><head><title>Annual Donation Statement {$year}</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #667eea; padding-bottom: 20px; }
            .summary { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; }
            .summary-item { display: inline-block; width: 30%; margin: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            th { background-color: #667eea; color: white; }
            .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; }
        </style></head><body>
        <div class='header'>
            <h1>Annual Donation Statement</h1>
            <h2>Year {$year}</h2>
            <p><strong>{$user->name}</strong></p>
            <p>{$user->email}</p>
        </div>
        <div class='summary'>
            <h3>Summary</h3>
            <div class='summary-item'><strong>Total Donations:</strong> {$summary['total_donations']}</div>
            <div class='summary-item'><strong>Total Amount:</strong> ₱" . number_format($summary['total_amount'], 2) . "</div>
            <div class='summary-item'><strong>Charities Supported:</strong> {$summary['charities_supported']}</div>
        </div>
        <table>
            <tr><th>Date</th><th>Charity</th><th>Campaign</th><th>Amount</th><th>Receipt No</th></tr>";
        
        foreach ($donations as $donation) {
            $html .= sprintf(
                "<tr><td>%s</td><td>%s</td><td>%s</td><td>₱%.2f</td><td>%s</td></tr>",
                $donation->created_at->format('M d, Y'),
                $donation->charity->name ?? 'N/A',
                $donation->campaign->title ?? 'General',
                $donation->amount,
                $donation->receipt_no ?? 'Pending'
            );
        }
        
        $html .= "</table>
        <div class='footer'>
            <p>This statement is for tax purposes. Keep this document for your records.</p>
            <p>Generated on " . date('F j, Y') . " | CharityHub</p>
        </div>
        </body></html>";
        
        // Generate PDF using dompdf
        $pdf = Pdf::loadHTML($html);
        return $pdf->output();
    }
}
