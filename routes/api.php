<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
  AuthController, CharityController, CampaignController, CampaignUpdateController, DonationController, FundUsageController, CharityPostController, TransparencyController, CharityFollowController, NotificationController, ReportController, CampaignCommentController, CategoryController, VolunteerController, LeaderboardController, DocumentExpiryController, LocationController, DonorRegistrationController, AnalyticsController, DashboardController, DonorAnalyticsController, EmailController, AuthEmailController, PaymentMethodController, TaxInfoController
};
use App\Models\Charity;
use App\Models\Campaign;
use App\Models\CampaignUpdate;
use App\Http\Controllers\Admin\{VerificationController, AdminActionLogController, FundTrackingController, UserActivityLogController, AdminDashboardController};

// Health
Route::get('/ping', fn () => ['ok' => true, 'time' => now()->toDateTimeString()]);

// Email System
Route::post('/test-email', [EmailController::class, 'sendTestEmail']);
Route::get('/email/test-connection', [EmailController::class, 'testConnection']);

// Authentication Email System
Route::post('/email/send-verification', [AuthEmailController::class, 'sendVerification']);
Route::post('/email/resend-verification', [AuthEmailController::class, 'resendVerification']);
Route::post('/email/password-reset', [AuthEmailController::class, 'sendPasswordReset']);
Route::post('/auth/reset-password', [AuthEmailController::class, 'resetPassword']);

// Email verification (web routes - redirects)
Route::get('/auth/verify-email', [AuthEmailController::class, 'verifyEmail'])->name('auth.verify-email');
Route::get('/auth/confirm-email-change', [AuthEmailController::class, 'confirmEmailChange'])->name('auth.confirm-email-change');

// TEST ROUTE - Check campaign data
Route::get('/test-campaign/{id}', function ($id) {
    $campaign = \App\Models\Campaign::with(['donationChannels', 'charity'])->find($id);
    if (!$campaign) return response()->json(['error' => 'Campaign not found'], 404);
    return response()->json([
        'id' => $campaign->id,
        'title' => $campaign->title,
        'problem' => $campaign->problem,
        'solution' => $campaign->solution,
        'expected_outcome' => $campaign->expected_outcome,
        'channels' => $campaign->donationChannels->map(fn($ch) => ['id' => $ch->id, 'type' => $ch->type, 'label' => $ch->label]),
        'channels_count' => $campaign->donationChannels->count(),
    ], 200, [], JSON_PRETTY_PRINT);
});

// TEST ROUTE - Check campaign updates
Route::get('/test-updates/{campaignId}', function ($campaignId) {
    $campaign = Campaign::find($campaignId);
    $updates = CampaignUpdate::where('campaign_id', $campaignId)->orderBy('created_at', 'desc')->get();
    return response()->json([
        'campaign_id' => $campaignId,
        'campaign_title' => $campaign ? $campaign->title : 'Not Found',
        'updates_count' => $updates->count(),
        'updates' => $updates->map(fn($u) => [
            'id' => $u->id,
            'title' => $u->title,
            'content' => substr($u->content, 0, 80) . '...',
            'is_milestone' => $u->is_milestone,
            'image_path' => $u->image_path,
            'created_at' => $u->created_at->toDateTimeString(),
        ]),
    ], 200, [], JSON_PRETTY_PRINT);
});

// Philippine Locations API (Public)
Route::get('/locations', [LocationController::class,'index']);
Route::get('/locations/regions', [LocationController::class,'getRegions']);
Route::get('/locations/regions/{regionCode}/provinces', [LocationController::class,'getProvinces']);
Route::get('/locations/regions/{regionCode}/provinces/{provinceCode}/cities', [LocationController::class,'getCities']);

// Auth
Route::post('/auth/register', [AuthController::class,'registerDonor']);
Route::post('/auth/register-charity', [AuthController::class,'registerCharityAdmin']);
Route::post('/auth/register-minimal', [AuthController::class,'registerMinimal']); // New minimal registration
Route::post('/auth/login', [AuthController::class,'login']);
Route::post('/auth/logout', [AuthController::class,'logout'])->middleware('auth:sanctum');

// Email Verification (new robust system)
Route::post('/auth/verify-email-code', [AuthController::class,'verifyEmailCode']); // Primary: code verification
Route::get('/auth/verify-email-token', [AuthController::class,'verifyEmailToken']); // Fallback: link verification
Route::post('/auth/resend-verification-code', [AuthController::class,'resendVerificationCode'])->middleware('throttle:5,1'); // Rate limited

// Account Retrieval (Public - no auth required)
Route::post('/auth/retrieve/donor', [AuthController::class,'retrieveDonorAccount']);
Route::post('/auth/retrieve/charity', [AuthController::class,'retrieveCharityAccount']);

// Profile & Account Management
Route::get('/me', [AuthController::class,'me'])->middleware('auth:sanctum');
Route::put('/me', [AuthController::class,'updateProfile'])->middleware('auth:sanctum');
Route::post('/me', [AuthController::class,'updateProfile'])->middleware('auth:sanctum'); // For FormData with file uploads
Route::post('/me/change-password', [AuthController::class,'changePassword'])->middleware('auth:sanctum');
Route::post('/me/deactivate', [AuthController::class,'deactivateAccount'])->middleware('auth:sanctum');
Route::post('/me/reactivate', [AuthController::class,'reactivateAccount'])->middleware('auth:sanctum');
Route::delete('/me', [AuthController::class,'deleteAccount'])->middleware('auth:sanctum');

// Change Email
Route::post('/me/change-email', [\App\Http\Controllers\SecurityController::class,'changeEmailRequest'])->middleware('auth:sanctum');
Route::post('/me/verify-email-change-code', [\App\Http\Controllers\SecurityController::class,'verifyEmailChangeCode'])->middleware('auth:sanctum');
Route::post('/auth/verify-email-change', [\App\Http\Controllers\SecurityController::class,'verifyEmailChange']);

// Two-Factor Authentication
Route::get('/me/2fa/status', [\App\Http\Controllers\SecurityController::class,'get2FAStatus'])->middleware('auth:sanctum');
Route::post('/me/2fa/enable', [\App\Http\Controllers\SecurityController::class,'enable2FA'])->middleware('auth:sanctum');
Route::post('/me/2fa/verify', [\App\Http\Controllers\SecurityController::class,'verify2FA'])->middleware('auth:sanctum');
Route::post('/me/2fa/disable', [\App\Http\Controllers\SecurityController::class,'disable2FA'])->middleware('auth:sanctum');

// Donor Registration (multi-step)
Route::post('/donors/register/draft', [DonorRegistrationController::class,'saveDraft']);
Route::post('/donors/register/verification', [DonorRegistrationController::class,'uploadVerification']);
Route::post('/donors/register/submit', [DonorRegistrationController::class,'submit']);

// Public directory
Route::get('/charities', [CharityController::class,'index']);
Route::get('/charities/{charity}', [CharityController::class,'show']);
Route::get('/charities/{charity}/channels', [CharityController::class,'channels']);
Route::get('/charities/{charity}/campaigns', [CampaignController::class,'index']);

// Public campaigns list (MUST come BEFORE wildcard routes!)
Route::get('/campaigns', [CampaignController::class,'publicIndex']);

// Public campaign filtering
Route::get('/campaigns/filter', [AnalyticsController::class,'filterCampaigns']);
Route::get('/campaigns/filter-options', [AnalyticsController::class,'filterOptions']);

// Campaign wildcard routes (AFTER specific routes)
Route::get('/campaigns/{campaign}', [CampaignController::class,'show']);
Route::get('/campaigns/{campaign}/fund-usage', [FundUsageController::class,'publicIndex']);
Route::get('/campaigns/{campaign}/updates', [CampaignUpdateController::class,'index']);
Route::get('/campaigns/{campaign}/updates/milestones', [CampaignUpdateController::class,'getMilestones']);
Route::get('/campaigns/{campaign}/updates/stats', [CampaignUpdateController::class,'getStats']);
Route::get('/campaigns/{campaign}/supporters', [CampaignController::class,'getSupporters']);
Route::get('/campaigns/{campaign}/donations', [CampaignController::class,'getDonations']);
Route::get('/campaigns/{campaign}/stats', [CampaignController::class,'getStats']);
Route::get('/campaigns/{campaign}/comments', [CampaignCommentController::class,'index']);

// Public video streaming
Route::get('/videos/{video}/stream', [\App\Http\Controllers\CampaignVideoController::class,'stream'])->name('api.videos.stream');

// Public charity posts (for donor news feed and charity profile)
Route::get('/posts', [CharityPostController::class,'index']);
Route::get('/charities/{charity}/posts', [CharityPostController::class,'getCharityPosts']);

// Public updates (for donor viewing)
Route::get('/charities/{charity}/updates', [\App\Http\Controllers\UpdateController::class,'getCharityUpdates']);

// Public categories
Route::get('/categories', [CategoryController::class,'index']);

// Public donation channels
Route::get('/campaigns/{campaign}/donation-channels', [\App\Http\Controllers\DonationChannelController::class,'index']);
Route::get('/charities/{charity}/donation-channels', [\App\Http\Controllers\DonationChannelController::class,'getCharityChannelsPublic']);

// Public statistics
Route::get('/public/stats', [DashboardController::class,'publicStats']);

// Public leaderboards
Route::get('/leaderboard/donors', [LeaderboardController::class,'topDonors']);
Route::get('/leaderboard/charities', [LeaderboardController::class,'topCharities']);
Route::get('/leaderboard/stats', [LeaderboardController::class,'donationStats']);
Route::get('/leaderboard/period', [LeaderboardController::class,'leaderboardByPeriod']);
Route::get('/charities/{charity}/leaderboard', [LeaderboardController::class,'topDonorsForCharity']);

// Public charity documents (for viewing by donors and public)
Route::get('/charities/{charity}/documents', [CharityController::class,'getDocuments']);

// Public charity officers (for viewing organization info)
Route::get('/charities/{charity}/officers', [\App\Http\Controllers\CharityOfficerController::class,'index']);

// Document viewing and downloading (authenticated users)
Route::middleware(['auth:sanctum'])->group(function(){
  Route::get('/documents/{document}/view', [\App\Http\Controllers\DocumentController::class,'view']);
  Route::get('/documents/{document}/download', [\App\Http\Controllers\DocumentController::class,'download']);
});

// Charity follow system (for donors)
Route::middleware(['auth:sanctum','role:donor'])->group(function(){
  Route::post('/charities/{charity}/follow', [CharityFollowController::class,'toggleFollow']);
  Route::get('/charities/{charity}/follow-status', [CharityFollowController::class,'getFollowStatus']);
  Route::get('/me/followed-charities', [CharityFollowController::class,'myFollowedCharities']);
});

// Public charity follow stats
Route::get('/charities/{charity}/followers-count', [CharityFollowController::class,'getFollowersCount']);
Route::get('/charities/{charity}/followers', [CharityFollowController::class,'getFollowers']);

// Public transparency (for approved charities only)
Route::get('/charities/{charity}/transparency', [TransparencyController::class,'publicTransparency']);

// Donor transparency dashboard
Route::middleware(['auth:sanctum','role:donor'])->group(function(){
  Route::get('/me/transparency', [TransparencyController::class,'donorTransparency']);
});

// Charity admin transparency dashboard
Route::middleware(['auth:sanctum','role:charity_admin'])->group(function(){
  Route::get('/charities/{charity}/transparency', [TransparencyController::class,'charityTransparency']);
});

// Donor actions
Route::middleware(['auth:sanctum','role:donor'])->group(function(){
  Route::get('/donor/dashboard', [DashboardController::class,'donorDashboard']);
  
  Route::post('/donations', [DonationController::class,'store']);
  Route::post('/donations/{donation}/proof', [DonationController::class,'uploadProof']);
  Route::post('/campaigns/{campaign}/donate', [DonationController::class,'submitManualDonation']);
  Route::post('/charities/{charity}/donate', [DonationController::class,'submitCharityDonation']);
  Route::get('/me/donations', [DonationController::class,'myDonations']);
  Route::get('/donations/{donation}/receipt', [DonationController::class,'downloadReceipt']);
  
  // Recurring Donations Management
  Route::get('/me/recurring-donations', [\App\Http\Controllers\RecurringDonationController::class,'index']);
  Route::patch('/recurring-donations/{id}', [\App\Http\Controllers\RecurringDonationController::class,'update']);
  Route::delete('/recurring-donations/{id}', [\App\Http\Controllers\RecurringDonationController::class,'destroy']);
  
  // Refund Requests (Donor)
  Route::post('/donations/{id}/refund', [DonationController::class,'requestRefund']);
  Route::get('/me/refunds', [DonationController::class,'getDonorRefunds']);
  
  // Donation Export & Statements
  Route::get('/me/donations/export', [DonationController::class,'exportDonations']);
  Route::get('/me/statements', [DonationController::class,'annualStatement']);
  
  // Donor Audit Reports (as specified in requirements)
  Route::get('/me/audit-report', [\App\Http\Controllers\DonorAuditReportController::class,'generateAuditReport']);
  Route::get('/me/export-csv', [\App\Http\Controllers\DonorAuditReportController::class,'exportCSV']);
  
  // Alternative donor report routes (backward compatibility)
  Route::get('/donor/reports/export-pdf', [\App\Http\Controllers\Donor\ReportController::class,'exportPDF']);
  Route::get('/donor/reports/export-csv', [\App\Http\Controllers\Donor\ReportController::class,'exportCSV']);
  
  // Following Charities
  Route::get('/me/following', [\App\Http\Controllers\FollowController::class,'index']);
  Route::delete('/follows/{id}', [\App\Http\Controllers\FollowController::class,'destroy']);
  
  // Saved/Bookmarked Campaigns
  Route::get('/me/saved', [\App\Http\Controllers\SavedItemController::class,'index']);
  Route::post('/me/saved', [\App\Http\Controllers\SavedItemController::class,'store']);
  Route::delete('/me/saved/{id}', [\App\Http\Controllers\SavedItemController::class,'destroy']);
  
  // Support Tickets
  Route::get('/support/tickets', [\App\Http\Controllers\SupportTicketController::class,'index']);
  Route::post('/support/tickets', [\App\Http\Controllers\SupportTicketController::class,'store']);
  Route::get('/support/tickets/{id}', [\App\Http\Controllers\SupportTicketController::class,'show']);
  Route::post('/support/tickets/{id}/messages', [\App\Http\Controllers\SupportTicketController::class,'addMessage']);
  
  // Messaging
  Route::get('/messages/conversations', [\App\Http\Controllers\MessageController::class,'conversations']);
  Route::get('/messages/conversation/{userId}', [\App\Http\Controllers\MessageController::class,'conversation']);
  Route::post('/messages', [\App\Http\Controllers\MessageController::class,'store']);
  Route::get('/messages/unread-count', [\App\Http\Controllers\MessageController::class,'unreadCount']);
  Route::patch('/messages/{id}/read', [\App\Http\Controllers\MessageController::class,'markAsRead']);
  
  // Active Sessions Management
  Route::get('/me/sessions', [\App\Http\Controllers\SessionController::class,'index']);
  Route::delete('/me/sessions/{id}', [\App\Http\Controllers\SessionController::class,'destroy']);
  Route::post('/me/sessions/revoke-all', [\App\Http\Controllers\SessionController::class,'destroyAll']);
  
  // Data Export
  Route::get('/me/export', [\App\Http\Controllers\DataExportController::class,'export']);
  
  // Notification Preferences
  Route::get('/me/notification-preferences', [\App\Http\Controllers\NotificationPreferenceController::class,'index']);
  Route::post('/me/notification-preferences', [\App\Http\Controllers\NotificationPreferenceController::class,'store']);

  // Charity Refund Requests (controller enforces charity ownership)
  Route::get('/charity/refunds', [\App\Http\Controllers\CharityRefundController::class,'index']);
  Route::get('/charity/refunds/{id}', [\App\Http\Controllers\CharityRefundController::class,'show']);
  Route::post('/charity/refunds/{id}/respond', [\App\Http\Controllers\CharityRefundController::class,'respond']);
  Route::get('/charity/refunds/statistics', [\App\Http\Controllers\CharityRefundController::class,'statistics']);

  // Campaign Comments (Donor can comment)
  Route::post('/campaigns/{campaign}/comments', [CampaignCommentController::class,'store']);
  
  // Volunteer Requests (Donor)
  Route::post('/campaigns/{campaign}/volunteer', [\App\Http\Controllers\CampaignVolunteerController::class,'store']);
  Route::get('/me/volunteer-requests', [\App\Http\Controllers\CampaignVolunteerController::class,'myRequests']);
  Route::delete('/volunteer-requests/{volunteer}', [\App\Http\Controllers\CampaignVolunteerController::class,'cancel']);
});

// Notifications (available to any authenticated user role)
Route::middleware(['auth:sanctum'])->group(function(){
  Route::get('/me/notifications', [NotificationController::class,'index']);
  Route::post('/notifications/{notification}/read', [NotificationController::class,'markAsRead']);
  Route::post('/notifications/mark-all-read', [NotificationController::class,'markAllAsRead']);
  Route::get('/notifications/unread-count', [NotificationController::class,'unreadCount']);
  Route::delete('/notifications/{notification}', [NotificationController::class,'destroy']);
  
  // Reports (available to any authenticated user - donor, charity, or admin)
  Route::post('/reports', [ReportController::class,'store']);
  Route::get('/me/reports', [ReportController::class,'myReports']);
  
  // Update interactions (available to any authenticated user)
  Route::post('/updates/{id}/like', [\App\Http\Controllers\UpdateController::class,'toggleLike']);
  Route::post('/updates/{id}/share', [\App\Http\Controllers\UpdateController::class,'shareUpdate']);
  Route::get('/updates/{id}/comments', [\App\Http\Controllers\UpdateController::class,'getComments']);
  Route::post('/updates/{id}/comments', [\App\Http\Controllers\UpdateController::class,'addComment']);
  Route::put('/comments/{id}', [\App\Http\Controllers\UpdateController::class,'updateComment']);
  Route::delete('/comments/{id}', [\App\Http\Controllers\UpdateController::class,'deleteComment']);
  Route::post('/comments/{id}/like', [\App\Http\Controllers\UpdateController::class,'toggleCommentLike']);
  
  // Account & Authentication Email Actions (Authenticated users)
  Route::post('/email/donor-reactivation', [AuthEmailController::class,'sendDonorReactivation']);
  Route::post('/email/charity-reactivation', [AuthEmailController::class,'sendCharityReactivation']);
  Route::post('/email/change-email', [AuthEmailController::class,'sendChangeEmail']);
  Route::post('/email/2fa-setup', [AuthEmailController::class,'send2FASetup']);
  Route::post('/email/account-status', [AuthEmailController::class,'sendAccountStatus']);
  
  // Payment Methods & Tax Info Management
  Route::get('/me/payment-methods', [PaymentMethodController::class,'index']);
  Route::post('/me/payment-methods', [PaymentMethodController::class,'store']);
  Route::put('/me/payment-methods/{id}', [PaymentMethodController::class,'update']);
  Route::delete('/me/payment-methods/{id}', [PaymentMethodController::class,'destroy']);
  
  Route::get('/me/tax-info', [TaxInfoController::class,'show']);
  Route::post('/me/tax-info', [TaxInfoController::class,'update']);
});

// System admin (for recurring donations processing and security)
Route::middleware(['auth:sanctum','role:admin'])->group(function(){
  Route::post('/admin/process-recurring-donations', [DonationController::class,'processRecurringDonations']);
  Route::get('/admin/security/activity-logs', [\App\Http\Controllers\Admin\SecurityController::class,'activityLogs']);
  Route::get('/admin/compliance/report', [\App\Http\Controllers\Admin\ComplianceController::class,'generateReport']);
});

// Charity admin
Route::middleware(['auth:sanctum','role:charity_admin'])->group(function(){
  Route::get('/charity/dashboard', [DashboardController::class,'charityDashboard']);
  
  // Charity 2FA Routes
  Route::get('/charity/2fa/status', [\App\Http\Controllers\CharitySecurityController::class,'get2FAStatus']);
  Route::post('/charity/2fa/enable', [\App\Http\Controllers\CharitySecurityController::class,'enable2FA']);
  Route::post('/charity/2fa/verify', [\App\Http\Controllers\CharitySecurityController::class,'verify2FA']);
  Route::post('/charity/2fa/disable', [\App\Http\Controllers\CharitySecurityController::class,'disable2FA']);
  
  Route::post('/charities', [CharityController::class,'store']);
  Route::put('/charities/{charity}', [CharityController::class,'update']);
  Route::post('/charity/profile/update', [CharityController::class,'updateProfile']);
  Route::post('/charities/{charity}/documents', [CharityController::class,'uploadDocument']);
  // Charity status management
  Route::post('/charities/{charity}/deactivate', [CharityController::class,'deactivate']);
  Route::post('/charities/{charity}/reactivation-request', [CharityController::class,'requestReactivation']);

  Route::post('/charities/{charity}/channels', [CharityController::class,'storeChannel']);

  // Get authenticated charity admin's campaigns
  Route::get('/charity/campaigns', [CampaignController::class,'myCampaigns']);
  Route::post('/charities/{charity}/campaigns', [CampaignController::class,'store']);
  Route::put('/campaigns/{campaign}', [CampaignController::class,'update']);
  Route::post('/campaigns/{campaign}/activate', [CampaignController::class,'activate']);
  Route::post('/campaigns/{campaign}/pause', [CampaignController::class,'pause']);
  Route::delete('/campaigns/{campaign}', [CampaignController::class,'destroy']);

  // Campaign Updates Management (Charity Admin Only)
  Route::post('/campaigns/{campaign}/updates', [CampaignUpdateController::class,'store']);
  Route::put('/campaign-updates/{id}', [CampaignUpdateController::class,'update']);
  Route::delete('/campaign-updates/{id}', [CampaignUpdateController::class,'destroy']);

  // Campaign Video Management (Charity Admin Only)
  Route::post('/campaigns/{campaign}/videos', [\App\Http\Controllers\VideoController::class,'store']);
  Route::get('/campaigns/{campaign}/videos', [\App\Http\Controllers\CampaignVideoController::class,'index']);
  
  // Update Video Management (Charity Admin Only)
  Route::post('/updates/{update}/videos', [\App\Http\Controllers\VideoController::class,'storeForUpdate']);
  Route::get('/updates/{update}/videos', [\App\Http\Controllers\VideoController::class,'indexForUpdate']);
  
  // Shared Video Operations
  Route::get('/videos/{video}', [\App\Http\Controllers\CampaignVideoController::class,'show']);
  Route::delete('/videos/{video}', [\App\Http\Controllers\CampaignVideoController::class,'destroy']);
  Route::patch('/videos/{video}', [\App\Http\Controllers\CampaignVideoController::class,'update']);

  // Donation Channels Management
  Route::get('/charity/donation-channels', [\App\Http\Controllers\DonationChannelController::class,'getCharityChannels']);
  Route::post('/charity/donation-channels', [\App\Http\Controllers\DonationChannelController::class,'store']);
  Route::put('/donation-channels/{channel}', [\App\Http\Controllers\DonationChannelController::class,'update']);
  Route::delete('/donation-channels/{channel}', [\App\Http\Controllers\DonationChannelController::class,'destroy']);
  Route::post('/donation-channels/{channel}/toggle', [\App\Http\Controllers\DonationChannelController::class,'toggleActive']);
  Route::post('/campaigns/{campaign}/donation-channels/attach', [\App\Http\Controllers\DonationChannelController::class,'attachToCampaign']);

  // Charity Officers Management (CRUD) - GET is public on line 165
  Route::post('/charities/{charity}/officers', [\App\Http\Controllers\CharityOfficerController::class,'store']);
  Route::put('/charity-officers/{officer}', [\App\Http\Controllers\CharityOfficerController::class,'update']);
  Route::delete('/charity-officers/{officer}', [\App\Http\Controllers\CharityOfficerController::class,'destroy']);

  // Campaign Volunteers Management
  Route::get('/campaigns/{campaign}/volunteers', [\App\Http\Controllers\CampaignVolunteerController::class,'index']);
  Route::post('/campaigns/{campaign}/volunteers/{volunteer}/respond', [\App\Http\Controllers\CampaignVolunteerController::class,'respond']);

  Route::get('/charities/{charity}/donations', [DonationController::class,'charityInbox']);
  Route::patch('/donations/{donation}/confirm', [DonationController::class,'confirm']);
  Route::patch('/donations/{donation}/status', [DonationController::class,'updateStatus']);

  // Refund Requests (Charity) — moved to auth:sanctum group below; controller enforces ownership

  // Fund Usage Management (CRUD)
  Route::get('/campaigns/{campaignId}/fund-usage', [FundUsageController::class,'index']);
  Route::post('/campaigns/{campaign}/fund-usage', [FundUsageController::class,'store']);
  Route::put('/fund-usage/{id}', [FundUsageController::class,'update']);
  Route::delete('/fund-usage/{id}', [FundUsageController::class,'destroy']);
  
  // Charity posts management
  Route::get('/my-posts', [CharityPostController::class,'getMyPosts']);
  Route::post('/posts', [CharityPostController::class,'store']);
  Route::put('/posts/{post}', [CharityPostController::class,'update']);
  Route::delete('/posts/{post}', [CharityPostController::class,'destroy']);
  
  // Volunteer Management
  Route::get('/charities/{charity}/volunteers', [VolunteerController::class,'index']);
  Route::post('/charities/{charity}/volunteers', [VolunteerController::class,'store']);
  Route::get('/charities/{charity}/volunteers/statistics', [VolunteerController::class,'statistics']);
  Route::get('/charities/{charity}/volunteers/{volunteer}', [VolunteerController::class,'show']);
  Route::put('/charities/{charity}/volunteers/{volunteer}', [VolunteerController::class,'update']);
  Route::delete('/charities/{charity}/volunteers/{volunteer}', [VolunteerController::class,'destroy']);
  
  // Document Expiry Status
  Route::get('/charities/{charity}/documents/expiry-status', [DocumentExpiryController::class,'getCharityDocumentStatus']);
  
  // Updates Management (Charity Admin)
  Route::get('/updates', [\App\Http\Controllers\UpdateController::class,'index']);
  Route::post('/updates', [\App\Http\Controllers\UpdateController::class,'store']);
  Route::put('/updates/{id}', [\App\Http\Controllers\UpdateController::class,'update']);
  Route::delete('/updates/{id}', [\App\Http\Controllers\UpdateController::class,'destroy']);
  Route::post('/updates/{id}/pin', [\App\Http\Controllers\UpdateController::class,'togglePin']);
  Route::patch('/comments/{id}/hide', [\App\Http\Controllers\UpdateController::class,'hideComment']);
  
  // Bin/Trash Management
  Route::get('/updates/trash', [\App\Http\Controllers\UpdateController::class,'getTrashed']);
  Route::post('/updates/{id}/restore', [\App\Http\Controllers\UpdateController::class,'restore']);
  Route::delete('/updates/{id}/force', [\App\Http\Controllers\UpdateController::class,'forceDelete']);
  
  // Charity Audit Reports (as specified in requirements)
  Route::get('/charity/audit-report', [\App\Http\Controllers\CharityAuditReportController::class,'generateAuditReport']);
  Route::get('/charity/export-csv', [\App\Http\Controllers\CharityAuditReportController::class,'exportCSV']);
  
  // Campaign Analytics Report (PDF Export)
  Route::get('/charity/campaign-analytics/export-pdf', [\App\Http\Controllers\CharityCampaignAnalyticsController::class,'exportPDF']);
  
  // Alternative charity report routes (backward compatibility)
  Route::get('/charity/reports/export-pdf', [\App\Http\Controllers\Charity\ReportController::class,'exportPDF']);
  Route::get('/charity/reports/export-csv', [\App\Http\Controllers\Charity\ReportController::class,'exportCSV']);
  
  // NEW: Charity Analytics API (Fixed zero values issue)
  Route::get('/charity/analytics/dashboard', [\App\Http\Controllers\Charity\CharityAnalyticsController::class,'getDashboardAnalytics']);
  Route::get('/charity/analytics/donations', [\App\Http\Controllers\Charity\CharityAnalyticsController::class,'getDonationReports']);
  Route::get('/charity/analytics/campaigns', [\App\Http\Controllers\Charity\CharityAnalyticsController::class,'getCampaignAnalytics']);
  Route::get('/charity/analytics/overfunded', [\App\Http\Controllers\Charity\CharityAnalyticsController::class,'getOverfundedCampaignsList']);
  Route::get('/charity/analytics/overfunded/{campaign}/timeline', [\App\Http\Controllers\Charity\CharityAnalyticsController::class,'getOverfundedCampaignTimeline']);
});

// System admin
Route::middleware(['auth:sanctum','role:admin'])->group(function(){
  Route::get('/admin/dashboard', [DashboardController::class,'adminDashboard']);
  
  Route::get('/admin/verifications', [VerificationController::class,'index']);
  Route::get('/admin/charities', [VerificationController::class,'getAllCharities']);
  Route::get('/admin/charities/{charity}', [VerificationController::class,'getCharityDetails']);
  Route::patch('/admin/charities/{charity}/activate', [VerificationController::class,'activateCharity']);
  Route::get('/admin/users', [VerificationController::class,'getUsers']);
  Route::patch('/admin/charities/{charity}/approve', [VerificationController::class,'approve']);
  Route::patch('/admin/charities/{charity}/reject', [VerificationController::class,'reject']);
  Route::patch('/admin/documents/{document}/approve', [VerificationController::class,'approveDocument']);
  Route::patch('/admin/documents/{document}/reject', [VerificationController::class,'rejectDocument']);
  Route::get('/admin/charities/{charity}/check-documents', [VerificationController::class,'checkCharityDocuments']); // Debug endpoint
  Route::patch('/admin/users/{user}/suspend', [VerificationController::class,'suspendUser']);
  Route::patch('/admin/users/{user}/activate', [VerificationController::class,'activateUser']);
  // Charity reactivation requests
  Route::get('/admin/charity-reactivation-requests', [VerificationController::class,'getCharityReactivationRequests']);
  Route::post('/admin/charity-reactivation-requests/{id}/approve', [VerificationController::class,'approveCharityReactivation']);
  Route::post('/admin/charity-reactivation-requests/{id}/reject', [VerificationController::class,'rejectCharityReactivation']);
  
  // Reactivation Requests
  Route::get('/admin/reactivation-requests', [\App\Http\Controllers\Admin\UserManagementController::class,'getReactivationRequests']);
  Route::post('/admin/reactivation-requests/{id}/approve', [\App\Http\Controllers\Admin\UserManagementController::class,'approveReactivation']);
  Route::post('/admin/reactivation-requests/{id}/reject', [\App\Http\Controllers\Admin\UserManagementController::class,'rejectReactivation']);
  
  // Reports Management
  Route::get('/admin/reports', [ReportController::class,'index']);
  Route::get('/admin/reports/statistics', [ReportController::class,'statistics']);
  Route::get('/admin/reports/{report}', [ReportController::class,'show']);
  Route::patch('/admin/reports/{report}/review', [ReportController::class,'review']);
  Route::delete('/admin/reports/{report}', [ReportController::class,'destroy']);
  
  // Suspension Management
  Route::post('/admin/reports/{report}/approve', [\App\Http\Controllers\Admin\SuspensionController::class,'approveReport']);
  Route::post('/admin/reports/{report}/reject', [\App\Http\Controllers\Admin\SuspensionController::class,'rejectReport']);
  
  // Admin Action Logs (old - keeping for backward compatibility)
  Route::get('/admin/action-logs', [AdminActionLogController::class,'index']);
  Route::get('/admin/action-logs/statistics', [AdminActionLogController::class,'statistics']);
  Route::get('/admin/action-logs/export', [AdminActionLogController::class,'export']);
  
  // User Activity Logs (new - tracks all user activities)
  Route::get('/admin/activity-logs', [UserActivityLogController::class,'index']);
  Route::get('/admin/activity-logs/statistics', [UserActivityLogController::class,'statistics']);
  Route::get('/admin/activity-logs/export', [UserActivityLogController::class,'export']);
  Route::get('/admin/activity-logs/export-pdf', [UserActivityLogController::class,'exportPDF']);
  
  // Dashboard Charts Data
  Route::get('/admin/dashboard/registrations-trend', [AdminDashboardController::class,'getRegistrationsTrend']);
  Route::get('/admin/dashboard/donations-trend', [AdminDashboardController::class,'getDonationsTrend']);
  Route::get('/admin/dashboard/charts-data', [AdminDashboardController::class,'getChartsData']);
  
  // Category Management
  Route::get('/admin/categories', [CategoryController::class,'adminIndex']);
  Route::post('/admin/categories', [CategoryController::class,'store']);
  Route::get('/admin/categories/statistics', [CategoryController::class,'statistics']);
  Route::put('/admin/categories/{category}', [CategoryController::class,'update']);
  Route::delete('/admin/categories/{category}', [CategoryController::class,'destroy']);
  
  // Email System Management
  Route::get('/admin/email/stats', [EmailController::class,'getStats']);
  Route::post('/admin/email/test', [EmailController::class,'sendTestEmail']);
  Route::post('/admin/email/send-verification', [EmailController::class,'sendVerification']);
  Route::post('/admin/email/send-password-reset', [EmailController::class,'sendPasswordReset']);
  
  // Comment Moderation
  Route::get('/admin/comments/pending', [CampaignCommentController::class,'pending']);
  Route::get('/admin/comments/statistics', [CampaignCommentController::class,'statistics']);
  Route::patch('/admin/comments/{comment}/moderate', [CampaignCommentController::class,'moderate']);
  Route::delete('/admin/comments/{comment}', [CampaignCommentController::class,'destroy']);
  
  // Document Expiry Management
  Route::get('/admin/documents/expiring', [DocumentExpiryController::class,'getExpiringDocuments']);
  Route::get('/admin/documents/expired', [DocumentExpiryController::class,'getExpiredDocuments']);
  Route::get('/admin/documents/expiry-statistics', [DocumentExpiryController::class,'getExpiryStatistics']);
  Route::patch('/admin/documents/{document}/expiry', [DocumentExpiryController::class,'updateDocumentExpiry']);
  
  // Fund Tracking
  Route::get('/admin/fund-tracking/statistics', [FundTrackingController::class,'getStatistics']);
  Route::get('/admin/fund-tracking/transactions', [FundTrackingController::class,'getTransactions']);
  Route::get('/admin/fund-tracking/chart-data', [FundTrackingController::class,'getChartData']);
  Route::get('/admin/fund-tracking/distribution', [FundTrackingController::class,'getDistributionData']);
  Route::get('/admin/fund-tracking/charity-breakdown', [FundTrackingController::class,'getCharityBreakdown']);
  Route::get('/admin/fund-tracking/campaign-type-breakdown', [FundTrackingController::class,'getCampaignTypeBreakdown']);
  Route::get('/admin/fund-tracking/fund-usage-categories', [FundTrackingController::class,'getFundUsageCategoryBreakdown']);
  Route::get('/admin/fund-tracking/donor-engagement', [FundTrackingController::class,'getDonorEngagement']);
  Route::get('/admin/fund-tracking/refund-tracking', [FundTrackingController::class,'getRefundTracking']);
  Route::get('/admin/fund-tracking/recurring-stats', [FundTrackingController::class,'getRecurringStats']);
  Route::get('/admin/fund-tracking/donation-purpose', [FundTrackingController::class,'getDonationPurposeBreakdown']);
  Route::get('/admin/fund-tracking/overfunded-campaigns', [FundTrackingController::class,'getOverfundedCampaigns']);
  Route::get('/admin/fund-tracking/campaign-timeline/{campaign}', [FundTrackingController::class,'getCampaignDonationTimeline']);
  Route::get('/admin/fund-tracking/export', [FundTrackingController::class,'exportData']);
  Route::get('/admin/fund-tracking/export-pdf', [FundTrackingController::class,'exportPlatformPDF']);
  
  // Platform Report (as specified in requirements)
  Route::get('/admin/platform-report', [\App\Http\Controllers\PlatformReportController::class,'generatePlatformReport']);
});

// Analytics Endpoints
Route::middleware(['auth:sanctum'])->group(function(){
  // Public analytics (available to all authenticated users)
  Route::get('/analytics/campaigns/types', [AnalyticsController::class,'campaignsByType']);
  Route::get('/analytics/campaigns/trending', [AnalyticsController::class,'trendingCampaigns']);
  Route::get('/analytics/campaigns/completed-receiving-donations', [AnalyticsController::class,'completedCampaignsReceivingDonations']);
  Route::get('/analytics/campaigns/{type}/stats', [AnalyticsController::class,'campaignTypeStats']);
  Route::get('/analytics/campaigns/{campaignId}/summary', [AnalyticsController::class,'campaignSummary']);
  
  // Advanced analytics (Phase 6)
  Route::get('/analytics/campaigns/{type}/advanced', [AnalyticsController::class,'advancedTypeAnalytics']);
  Route::get('/analytics/trending-explanation/{type}', [AnalyticsController::class,'trendingExplanation']);
  
  // Enhanced trending & activity analytics
  Route::get('/analytics/growth-by-type', [AnalyticsController::class,'growthByType']);
  Route::get('/analytics/most-improved', [AnalyticsController::class,'mostImprovedCampaign']);
  Route::get('/analytics/activity-timeline', [AnalyticsController::class,'activityTimeline']);
  
  // Overview analytics
  Route::get('/analytics/overview', [AnalyticsController::class,'getOverviewSummary']);
  Route::get('/analytics/trends', [AnalyticsController::class,'getTrendsData']);
  Route::get('/analytics/insights', [AnalyticsController::class,'getInsights']);
  Route::get('/analytics/summary', [AnalyticsController::class,'summary']);
  Route::get('/analytics/campaigns/locations', [AnalyticsController::class,'campaignLocations']);
  Route::get('/analytics/campaigns/by-location', [AnalyticsController::class,'getCampaignsByLocation']);
  Route::get('/analytics/campaigns/location-summary', [AnalyticsController::class,'getLocationSummary']);
  Route::get('/analytics/campaigns/location-filters', [AnalyticsController::class,'getLocationFilters']);
  Route::get('/analytics/campaigns/beneficiaries', [AnalyticsController::class,'getCampaignBeneficiaryDistribution']);
  Route::get('/analytics/campaigns/temporal', [AnalyticsController::class,'temporalTrends']);
  Route::get('/analytics/campaigns/fund-ranges', [AnalyticsController::class,'fundRanges']);
  Route::get('/analytics/campaigns/top-performers', [AnalyticsController::class,'topPerformers']);
  Route::get('/analytics/campaign-type-insights', [AnalyticsController::class,'getCampaignTypeInsights']);
  
  // Donor-specific analytics (protected)
  Route::get('/analytics/donors/{donorId}/summary', [AnalyticsController::class,'donorSummary']);
  
  // Donor-facing site-wide campaign analytics (public aggregated data)
  Route::get('/donor-analytics/summary', [DonorAnalyticsController::class, 'summary']);
  Route::post('/donor-analytics/query', [DonorAnalyticsController::class, 'query']);
  Route::get('/donor-analytics/campaign/{id}', [DonorAnalyticsController::class, 'campaignDetails']);
  Route::get('/donor-analytics/donor/{id}/overview', [DonorAnalyticsController::class, 'donorOverview']);
});

// Donor Profile Routes
Route::get('/donors/{id}', [\App\Http\Controllers\API\DonorProfileController::class, 'show']);
Route::get('/donors/{id}/activity', [\App\Http\Controllers\API\DonorProfileController::class, 'activity']);
Route::get('/donors/{id}/milestones', [\App\Http\Controllers\API\DonorProfileController::class, 'milestones']);
Route::get('/donors/{id}/badges', [\App\Http\Controllers\API\DonorProfileController::class, 'badges']);
Route::middleware('auth:sanctum')->group(function () {
    Route::put('/donors/{id}/profile', [\App\Http\Controllers\API\DonorProfileController::class, 'update']);
    Route::post('/donors/{id}/image', [\App\Http\Controllers\API\DonorProfileController::class, 'updateImage']);
});

// routes/api.php
Route::get('/metrics', function () {
    try {
        return response()->json([
            'total_users' => \App\Models\User::count(),
            'total_donors' => \App\Models\User::where('role', 'donor')->count(),
            'total_charity_admins' => \App\Models\User::where('role', 'charity_admin')->count(),
            'charities' => \App\Models\Charity::where('verification_status','approved')->count(),
            'pending_verifications' => \App\Models\Charity::where('verification_status','pending')->count(),
            'campaigns' => \App\Models\Campaign::count(),
            'donations' => \App\Models\Donation::count(),
        ]);
    } catch (\Exception $e) {
        \Log::error('Metrics endpoint error: ' . $e->getMessage());
        return response()->json([
            'total_users' => 0,
            'total_donors' => 0,
            'total_charity_admins' => 0,
            'charities' => 0,
            'pending_verifications' => 0,
            'campaigns' => 0,
            'donations' => 0,
            'error' => 'Unable to fetch metrics'
        ], 200);
    }
});
