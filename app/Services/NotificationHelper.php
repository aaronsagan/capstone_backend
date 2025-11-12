<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Donation;
use App\Models\Charity;
use App\Models\Campaign;
use App\Models\RefundRequest;
use App\Models\Report;
use Illuminate\Support\Facades\Log;

class NotificationHelper
{
    /**
     * Create a notification for a user
     */
    public static function create(User $user, string $type, string $title, string $message, array $data = [])
    {
        try {
            return Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'read' => false,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create notification: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Notify donor about donation confirmation
     */
    public static function donationConfirmed(Donation $donation)
    {
        if ($donation->is_anonymous || !$donation->donor) {
            return;
        }

        self::create(
            $donation->donor,
            'donation_confirmed',
            'Donation Confirmed',
            "Your donation of ₱" . number_format($donation->amount, 2) . " to {$donation->charity->name} has been confirmed.",
            [
                'donation_id' => $donation->id,
                'charity_id' => $donation->charity_id,
                'amount' => $donation->amount,
            ]
        );
    }

    /**
     * Notify charity about new donation
     */
    public static function donationReceived(Donation $donation)
    {
        if (!$donation->charity->owner) {
            return;
        }

        $donorName = $donation->is_anonymous ? 'An anonymous donor' : $donation->donor->name;

        self::create(
            $donation->charity->owner,
            'donation_received',
            'New Donation Received',
            "{$donorName} donated ₱" . number_format($donation->amount, 2) . " to your charity.",
            [
                'donation_id' => $donation->id,
                'donor_id' => $donation->is_anonymous ? null : $donation->donor_id,
                'amount' => $donation->amount,
            ]
        );
    }

    /**
     * Notify charity about verification status
     */
    public static function charityVerificationStatus(Charity $charity, string $status)
    {
        if (!$charity->owner) {
            return;
        }

        $title = $status === 'approved' ? 'Charity Approved' : 'Charity Verification Update';
        $message = $status === 'approved' 
            ? "Congratulations! Your charity '{$charity->name}' has been approved and is now live."
            : "Your charity '{$charity->name}' verification status has been updated to: {$status}.";

        self::create(
            $charity->owner,
            'charity_verification',
            $title,
            $message,
            [
                'charity_id' => $charity->id,
                'status' => $status,
                'rejection_reason' => $charity->rejection_reason,
            ]
        );
    }

    /**
     * Notify donors about campaign updates
     */
    public static function campaignUpdate(Campaign $campaign, string $updateType = 'created')
    {
        // Get all donors who donated to this charity
        $donors = User::whereHas('donations', function($q) use ($campaign) {
            $q->where('charity_id', $campaign->charity_id);
        })->where('role', 'donor')->get();

        $messages = [
            'created' => "New campaign '{$campaign->title}' has been launched by {$campaign->charity->name}!",
            'updated' => "Campaign '{$campaign->title}' has been updated.",
            'completed' => "Campaign '{$campaign->title}' has reached its goal! Thank you for your support.",
            'ended' => "Campaign '{$campaign->title}' has ended.",
        ];

        $message = $messages[$updateType] ?? "Campaign '{$campaign->title}' has been updated.";

        foreach ($donors as $donor) {
            self::create(
                $donor,
                'campaign_update',
                'Campaign Update',
                $message,
                [
                    'campaign_id' => $campaign->id,
                    'charity_id' => $campaign->charity_id,
                    'update_type' => $updateType,
                ]
            );
        }
    }

    /**
     * Notify donor about refund request status
     */
    public static function refundRequestStatus(RefundRequest $refundRequest, string $status)
    {
        if (!$refundRequest->donation->donor) {
            return;
        }

        $messages = [
            'pending' => "Your refund request for ₱" . number_format($refundRequest->donation->amount, 2) . " has been submitted and is under review.",
            'approved' => "Your refund request for ₱" . number_format($refundRequest->donation->amount, 2) . " has been approved.",
            'rejected' => "Your refund request for ₱" . number_format($refundRequest->donation->amount, 2) . " has been rejected.",
        ];

        self::create(
            $refundRequest->donation->donor,
            'refund_status',
            'Refund Request Update',
            $messages[$status] ?? "Your refund request status has been updated.",
            [
                'refund_request_id' => $refundRequest->id,
                'donation_id' => $refundRequest->donation_id,
                'status' => $status,
            ]
        );
    }

    /**
     * Notify charity about refund request
     */
    public static function refundRequestReceived(RefundRequest $refundRequest)
    {
        if (!$refundRequest->donation->charity->owner) {
            return;
        }

        self::create(
            $refundRequest->donation->charity->owner,
            'refund_request',
            'New Refund Request',
            "A refund request for ₱" . number_format($refundRequest->donation->amount, 2) . " has been submitted.",
            [
                'refund_request_id' => $refundRequest->id,
                'donation_id' => $refundRequest->donation_id,
            ]
        );
    }

    /**
     * Notify admin about new charity registration
     */
    public static function newCharityRegistration(Charity $charity)
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            self::create(
                $admin,
                'charity_registration',
                'New Charity Registration',
                "New charity '{$charity->name}' has registered and is awaiting verification.",
                [
                    'charity_id' => $charity->id,
                ]
            );
        }
    }

    /**
     * Notify admin about new report
     */
    public static function newReport($report)
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            self::create(
                $admin,
                'new_report',
                'New Report Submitted',
                "A new report has been submitted: {$report->reason}",
                [
                    'report_id' => $report->id,
                    'reportable_type' => $report->reportable_type,
                    'reportable_id' => $report->reportable_id,
                ]
            );
        }
    }

    /**
     * Notify user about report status
     */
    public static function reportStatus($report, string $status)
    {
        if (!$report->reporter) {
            return;
        }

        $messages = [
            'pending' => "Your report has been submitted and is under review.",
            'reviewed' => "Your report has been reviewed by our team.",
            'resolved' => "Your report has been resolved. Thank you for helping us maintain a safe community.",
            'dismissed' => "Your report has been reviewed and dismissed.",
        ];

        self::create(
            $report->reporter,
            'report_status',
            'Report Status Update',
            $messages[$status] ?? "Your report status has been updated.",
            [
                'report_id' => $report->id,
                'status' => $status,
            ]
        );
    }

    /**
     * Notify donors about fund usage
     */
    public static function fundUsageLogged($fundUsage)
    {
        // Get donors who contributed to this charity
        $donors = User::whereHas('donations', function($q) use ($fundUsage) {
            $q->where('charity_id', $fundUsage->charity_id);
        })->where('role', 'donor')->get();

        foreach ($donors as $donor) {
            self::create(
                $donor,
                'fund_usage',
                'Fund Usage Update',
                "{$fundUsage->charity->name} used ₱" . number_format($fundUsage->amount, 2) . " for {$fundUsage->category}.",
                [
                    'fund_usage_id' => $fundUsage->id,
                    'charity_id' => $fundUsage->charity_id,
                    'amount' => $fundUsage->amount,
                    'category' => $fundUsage->category,
                ]
            );
        }
    }

    /**
     * Notify user about account status change
     */
    public static function accountStatusChanged(User $user, string $status)
    {
        $messages = [
            'active' => "Your account has been activated. Welcome back!",
            'suspended' => "Your account has been suspended. Please contact support for more information.",
            'banned' => "Your account has been banned due to policy violations.",
        ];

        self::create(
            $user,
            'account_status',
            'Account Status Update',
            $messages[$status] ?? "Your account status has been updated.",
            [
                'status' => $status,
            ]
        );
    }

    /**
     * Notify charity about campaign milestone
     */
    public static function campaignMilestone(Campaign $campaign, int $percentage)
    {
        if (!$campaign->charity->owner) {
            return;
        }

        self::create(
            $campaign->charity->owner,
            'campaign_milestone',
            'Campaign Milestone Reached',
            "Your campaign '{$campaign->title}' has reached {$percentage}% of its goal!",
            [
                'campaign_id' => $campaign->id,
                'percentage' => $percentage,
                'current_amount' => $campaign->current_amount,
                'target_amount' => $campaign->target_amount,
            ]
        );
    }

    /**
     * Notify charity about low funds
     */
    public static function lowFundsWarning(Charity $charity)
    {
        if (!$charity->owner) {
            return;
        }

        self::create(
            $charity->owner,
            'low_funds',
            'Low Funds Warning',
            "Your charity's available funds are running low. Current balance: ₱" . number_format($charity->balance ?? 0, 2),
            [
                'charity_id' => $charity->id,
                'balance' => $charity->balance ?? 0,
            ]
        );
    }

    /**
     * Notify user about new message
     */
    public static function newMessage($message)
    {
        if (!$message->receiver) {
            return;
        }

        self::create(
            $message->receiver,
            'new_message',
            'New Message',
            "You have a new message from {$message->sender->name}.",
            [
                'message_id' => $message->id,
                'sender_id' => $message->sender_id,
            ]
        );
    }

    /**
     * Notify charity about campaign expiring soon
     */
    public static function campaignExpiringSoon(Campaign $campaign, int $daysLeft)
    {
        if (!$campaign->charity->owner) {
            return;
        }

        self::create(
            $campaign->charity->owner,
            'campaign_expiring',
            'Campaign Expiring Soon',
            "Your campaign '{$campaign->title}' will end in {$daysLeft} days.",
            [
                'campaign_id' => $campaign->id,
                'days_left' => $daysLeft,
                'end_date' => $campaign->end_date,
            ]
        );
    }

    /**
     * Notify donors about campaign completion report
     */
    public static function campaignCompletionReport(Campaign $campaign, $update)
    {
        // Get all donors who donated to this campaign
        $donors = User::whereHas('donations', function($q) use ($campaign) {
            $q->where('campaign_id', $campaign->id)
              ->where('status', 'completed');
        })->where('role', 'donor')->get();

        foreach ($donors as $donor) {
            self::create(
                $donor,
                'campaign_completion',
                'Campaign Completion Report',
                "'{$campaign->title}' has posted a completion report. See how your donation made an impact!",
                [
                    'campaign_id' => $campaign->id,
                    'update_id' => $update->id,
                    'charity_id' => $campaign->charity_id,
                ]
            );
        }
    }

    /**
     * Notify donors about new campaign update
     */
    public static function newCampaignUpdate(Campaign $campaign, $update)
    {
        // Get all donors who donated to this campaign
        $donors = User::whereHas('donations', function($q) use ($campaign) {
            $q->where('campaign_id', $campaign->id)
              ->where('status', 'completed');
        })->where('role', 'donor')->get();

        foreach ($donors as $donor) {
            self::create(
                $donor,
                'campaign_update_posted',
                'New Campaign Update',
                "'{$campaign->title}' has posted a new update: {$update->title}",
                [
                    'campaign_id' => $campaign->id,
                    'update_id' => $update->id,
                    'charity_id' => $campaign->charity_id,
                ]
            );
        }
    }

    /**
     * Notify donors about fund usage for specific campaign
     */
    public static function campaignFundUsage(Campaign $campaign, $fundUsage)
    {
        // Get donors who contributed to this campaign
        $donors = User::whereHas('donations', function($q) use ($campaign) {
            $q->where('campaign_id', $campaign->id)
              ->where('status', 'completed');
        })->where('role', 'donor')->get();

        foreach ($donors as $donor) {
            self::create(
                $donor,
                'campaign_fund_usage',
                'Fund Usage Update',
                "'{$campaign->title}' used ₱" . number_format($fundUsage->amount, 2) . " for {$fundUsage->category}.",
                [
                    'fund_usage_id' => $fundUsage->id,
                    'campaign_id' => $campaign->id,
                    'charity_id' => $campaign->charity_id,
                    'amount' => $fundUsage->amount,
                    'category' => $fundUsage->category,
                ]
            );
        }
    }

    /**
     * Notify charity about missing completion requirements
     */
    public static function campaignCompletionReminder(Campaign $campaign)
    {
        if (!$campaign->charity->owner) {
            return;
        }

        $requirements = [];
        if ($campaign->needsCompletionReport()) {
            $requirements[] = 'completion report';
        }
        if ($campaign->needsFundUsageLogs()) {
            $requirements[] = 'fund usage logs';
        }

        if (empty($requirements)) {
            return;
        }

        $requirementText = implode(' and ', $requirements);

        self::create(
            $campaign->charity->owner,
            'completion_reminder',
            'Campaign Completion Required',
            "Your campaign '{$campaign->title}' has ended. Please submit {$requirementText} to complete the campaign.",
            [
                'campaign_id' => $campaign->id,
                'needs_completion_report' => $campaign->needsCompletionReport(),
                'needs_fund_usage_logs' => $campaign->needsFundUsageLogs(),
            ]
        );
    }

    /**
     * Notify charity when donor follows them
     */
    public static function charityFollowed(Charity $charity, User $follower)
    {
        if (!$charity->owner) {
            return;
        }

        self::create(
            $charity->owner,
            'new_follower',
            'New Follower',
            "{$follower->name} is now following your charity.",
            [
                'charity_id' => $charity->id,
                'follower_id' => $follower->id,
            ]
        );
    }

    /**
     * Notify charity when donor likes their campaign
     */
    public static function campaignLiked(Campaign $campaign, User $user)
    {
        if (!$campaign->charity->owner) {
            return;
        }

        self::create(
            $campaign->charity->owner,
            'campaign_liked',
            'Campaign Liked',
            "{$user->name} liked your campaign '{$campaign->title}'.",
            [
                'campaign_id' => $campaign->id,
                'user_id' => $user->id,
            ]
        );
    }

    /**
     * Notify charity when donor saves their campaign
     */
    public static function campaignSaved(Campaign $campaign, User $user)
    {
        if (!$campaign->charity->owner) {
            return;
        }

        self::create(
            $campaign->charity->owner,
            'campaign_saved',
            'Campaign Saved',
            "{$user->name} saved your campaign '{$campaign->title}'.",
            [
                'campaign_id' => $campaign->id,
                'user_id' => $user->id,
            ]
        );
    }

    /**
     * Notify charity when someone comments on their campaign
     */
    public static function campaignCommented(Campaign $campaign, User $commenter, $comment)
    {
        if (!$campaign->charity->owner) {
            return;
        }

        self::create(
            $campaign->charity->owner,
            'new_comment',
            'New Comment',
            "{$commenter->name} commented on your campaign '{$campaign->title}'.",
            [
                'campaign_id' => $campaign->id,
                'comment_id' => $comment->id,
                'commenter_id' => $commenter->id,
            ]
        );
    }

    /**
     * Notify donor when their donation is verified/approved
     */
    public static function donationVerified(Donation $donation)
    {
        if ($donation->is_anonymous || !$donation->donor) {
            return;
        }

        self::create(
            $donation->donor,
            'donation_verified',
            'Donation Verified',
            "Your donation of ₱" . number_format($donation->amount, 2) . " has been verified and approved!",
            [
                'donation_id' => $donation->id,
                'charity_id' => $donation->charity_id,
                'amount' => $donation->amount,
            ]
        );
    }

    /**
     * Notify admins about new user registration
     */
    public static function newUserRegistration(User $user)
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            self::create(
                $admin,
                'new_user',
                'New User Registration',
                "New {$user->role} registered: {$user->name} ({$user->email})",
                [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                ]
            );
        }
    }

    /**
     * Notify admins about pending charity verification
     */
    public static function pendingCharityVerification(Charity $charity)
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            self::create(
                $admin,
                'pending_verification',
                'Pending Charity Verification',
                "Charity '{$charity->name}' is pending verification.",
                [
                    'charity_id' => $charity->id,
                ]
            );
        }
    }

    /**
     * Notify admins about new donation (for monitoring)
     */
    public static function newDonationAdmin(Donation $donation)
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            self::create(
                $admin,
                'new_donation',
                'New Donation',
                "New donation of ₱" . number_format($donation->amount, 2) . " to {$donation->charity->name}",
                [
                    'donation_id' => $donation->id,
                    'charity_id' => $donation->charity_id,
                    'amount' => $donation->amount,
                ]
            );
        }
    }

    /**
     * Notify admins about new fund usage log
     */
    public static function newFundUsageAdmin($fundUsage)
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            self::create(
                $admin,
                'new_fund_usage',
                'New Fund Usage',
                "{$fundUsage->charity->name} logged fund usage of ₱" . number_format($fundUsage->amount, 2),
                [
                    'fund_usage_id' => $fundUsage->id,
                    'charity_id' => $fundUsage->charity_id,
                    'amount' => $fundUsage->amount,
                ]
            );
        }
    }

    /**
     * Notify donors about new campaign from charity they follow
     */
    public static function newCampaignFromFollowedCharity(Campaign $campaign)
    {
        // Get all users following this charity
        $followers = User::whereHas('followedCharities', function($q) use ($campaign) {
            $q->where('charity_id', $campaign->charity_id);
        })->where('role', 'donor')->get();

        foreach ($followers as $follower) {
            self::create(
                $follower,
                'new_campaign',
                'New Campaign',
                "{$campaign->charity->name} launched a new campaign: '{$campaign->title}'",
                [
                    'campaign_id' => $campaign->id,
                    'charity_id' => $campaign->charity_id,
                ]
            );
        }
    }

    /**
     * Notify admins when user deactivates account
     */
    public static function userDeactivated(User $user, ?string $reason = null)
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            self::create(
                $admin,
                'user_deactivated',
                'User Account Deactivated',
                "{$user->name} ({$user->email}) has deactivated their account.",
                [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'user_role' => $user->role,
                    'reason' => $reason,
                ]
            );
        }
    }

    /**
     * Notify admins when user requests reactivation
     */
    public static function reactivationRequest(User $user)
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            self::create(
                $admin,
                'reactivation_request',
                'Account Reactivation Request',
                "{$user->name} ({$user->email}) is requesting to reactivate their account.",
                [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'user_role' => $user->role,
                ]
            );
        }
    }

    /**
     * Notify user when account is reactivated
     */
    public static function accountReactivated(User $user)
    {
        self::create(
            $user,
            'account_reactivated',
            'Account Reactivated',
            'Your account has been reactivated by an administrator. You can now log in.',
            [
                'reactivated_at' => now()->toISOString(),
            ]
        );
    }

    /**
     * Notify admins when a charity is deactivated by its owner
     */
    public static function charityDeactivated(Charity $charity, ?string $reason = null)
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            self::create(
                $admin,
                'charity_deactivated',
                'Charity Deactivated',
                "Charity '{$charity->name}' has been deactivated by its owner.",
                [
                    'charity_id' => $charity->id,
                    'owner_id' => $charity->owner_id,
                    'reason' => $reason,
                ]
            );
        }
    }

    /**
     * Notify admins when a charity requests reactivation
     */
    public static function charityReactivationRequest(Charity $charity)
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            self::create(
                $admin,
                'charity_reactivation_request',
                'Charity Reactivation Request',
                "Charity '{$charity->name}' is requesting reactivation.",
                [
                    'charity_id' => $charity->id,
                    'owner_id' => $charity->owner_id,
                ]
            );
        }
    }

    /**
     * Notify charity owner when charity is reactivated by admin
     */
    public static function charityReactivated(Charity $charity)
    {
        if (!$charity->owner) {
            return;
        }

        self::create(
            $charity->owner,
            'charity_reactivated',
            'Charity Reactivated',
            "Your charity '{$charity->name}' has been reactivated. You can now continue operations.",
            [
                'charity_id' => $charity->id,
                'reactivated_at' => now()->toISOString(),
            ]
        );
    }

    // Suspension notifications
    public static function accountSuspended(User $user, string $reason, $suspendedUntil, int $penaltyDays)
    {
        self::create(
            $user,
            'account_suspended',
            'Account Suspended',
            "Your account has been suspended until " . $suspendedUntil->format('M d, Y h:i A') . " (" . $penaltyDays . " days) due to: " . $reason,
            [
                'suspended_until' => $suspendedUntil->toISOString(),
                'penalty_days' => $penaltyDays,
                'reason' => $reason,
            ]
        );
    }

    public static function newReportSubmitted(Report $report)
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            self::create(
                $admin,
                'new_report_submitted',
                'New Report Submitted',
                "A new " . $report->severity . " severity report has been submitted regarding " . $report->target_type . " #" . $report->target_id,
                [
                    'report_id' => $report->id,
                    'target_type' => $report->target_type,
                    'target_id' => $report->target_id,
                    'severity' => $report->severity,
                    'report_type' => $report->report_type,
                ]
            );
        }
    }

    /**
     * Notify charity about new volunteer request
     */
    public static function volunteerRequestSubmitted($volunteer, $campaign)
    {
        if (!$campaign->charity || !$campaign->charity->owner) {
            return;
        }

        self::create(
            $campaign->charity->owner,
            'volunteer_request',
            'New Volunteer Request',
            "{$volunteer->user->name} wants to volunteer for '{$campaign->title}'.",
            [
                'volunteer_id' => $volunteer->id,
                'campaign_id' => $campaign->id,
                'user_id' => $volunteer->user_id,
            ]
        );
    }

    /**
     * Notify volunteer about request response
     */
    public static function volunteerRequestResponded($volunteer, $campaign, $status)
    {
        if (!$volunteer->user) {
            return;
        }

        $title = $status === 'approved' ? 'Volunteer Request Approved' : 'Volunteer Request Update';
        $message = $status === 'approved'
            ? "Great news! Your volunteer request for '{$campaign->title}' has been approved."
            : "Your volunteer request for '{$campaign->title}' has been {$status}.";

        self::create(
            $volunteer->user,
            'volunteer_response',
            $title,
            $message,
            [
                'volunteer_id' => $volunteer->id,
                'campaign_id' => $campaign->id,
                'status' => $status,
            ]
        );
    }
}
