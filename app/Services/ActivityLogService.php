<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Log user activity
     * 
     * @param string $action The action being performed
     * @param array $details Additional details about the action
     * @param int|null $userId Override user ID (default: current authenticated user)
     * @return ActivityLog|null
     */
    public static function log(string $action, array $details = [], ?int $userId = null): ?ActivityLog
    {
        try {
            $user = $userId ? \App\Models\User::find($userId) : Auth::user();
            
            if (!$user) {
                \Log::warning('ActivityLogService: No user found for logging', [
                    'action' => $action,
                    'user_id' => $userId
                ]);
                return null;
            }

            return ActivityLog::create([
                'user_id' => $user->id,
                'user_role' => $user->role,
                'action' => $action,
                'details' => $details,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'session_id' => session()->getId(),
            ]);
        } catch (\Exception $e) {
            \Log::error('ActivityLogService: Failed to log activity', [
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Log authentication actions
     */
    public static function logLogin(int $userId): ?ActivityLog
    {
        return self::log('login', [
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logLogout(int $userId): ?ActivityLog
    {
        return self::log('logout', [
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logRegistration(int $userId, string $role): ?ActivityLog
    {
        return self::log('user_registered', [
            'role' => $role,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    /**
     * Log profile actions
     */
    public static function logProfileUpdate(int $userId, array $changes = []): ?ActivityLog
    {
        return self::log('profile_updated', [
            'changes' => $changes,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logPasswordChange(int $userId): ?ActivityLog
    {
        return self::log('password_changed', [
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    /**
     * Log donation actions
     */
    public static function logDonationCreated(int $userId, int $donationId, float $amount, ?int $campaignId = null): ?ActivityLog
    {
        return self::log('donation_created', [
            'donation_id' => $donationId,
            'amount' => $amount,
            'campaign_id' => $campaignId,
            'resource_type' => 'Donation',
            'resource_id' => $donationId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logDonationConfirmed(int $userId, int $donationId, float $amount): ?ActivityLog
    {
        return self::log('donation_confirmed', [
            'donation_id' => $donationId,
            'amount' => $amount,
            'resource_type' => 'Donation',
            'resource_id' => $donationId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logDonationRejected(int $userId, int $donationId, ?string $reason = null): ?ActivityLog
    {
        return self::log('donation_rejected', [
            'donation_id' => $donationId,
            'reason' => $reason,
            'resource_type' => 'Donation',
            'resource_id' => $donationId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logDonationUpdated(int $userId, int $donationId, string $status): ?ActivityLog
    {
        return self::log('donation_updated', [
            'donation_id' => $donationId,
            'status' => $status,
            'resource_type' => 'Donation',
            'resource_id' => $donationId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    /**
     * Log campaign actions
     */
    public static function logCampaignCreated(int $userId, int $campaignId, string $title): ?ActivityLog
    {
        return self::log('campaign_created', [
            'campaign_id' => $campaignId,
            'campaign_title' => $title,
            'resource_type' => 'Campaign',
            'resource_id' => $campaignId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logCampaignUpdated(int $userId, int $campaignId, string $title, array $changes = []): ?ActivityLog
    {
        return self::log('campaign_updated', [
            'campaign_id' => $campaignId,
            'campaign_title' => $title,
            'changes' => $changes,
            'resource_type' => 'Campaign',
            'resource_id' => $campaignId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logCampaignActivated(int $userId, int $campaignId, string $title): ?ActivityLog
    {
        return self::log('campaign_activated', [
            'campaign_id' => $campaignId,
            'campaign_title' => $title,
            'resource_type' => 'Campaign',
            'resource_id' => $campaignId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logCampaignPaused(int $userId, int $campaignId, string $title): ?ActivityLog
    {
        return self::log('campaign_paused', [
            'campaign_id' => $campaignId,
            'campaign_title' => $title,
            'resource_type' => 'Campaign',
            'resource_id' => $campaignId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logCampaignDeleted(int $userId, int $campaignId, string $title): ?ActivityLog
    {
        return self::log('campaign_deleted', [
            'campaign_id' => $campaignId,
            'campaign_title' => $title,
            'resource_type' => 'Campaign',
            'resource_id' => $campaignId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logCampaignCompleted(int $userId, int $campaignId, string $title): ?ActivityLog
    {
        return self::log('campaign_completed', [
            'campaign_id' => $campaignId,
            'campaign_title' => $title,
            'resource_type' => 'Campaign',
            'resource_id' => $campaignId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    /**
     * Log charity actions
     */
    public static function logCharityApproved(int $adminId, int $charityId, string $charityName): ?ActivityLog
    {
        return self::log('charity_approved', [
            'charity_id' => $charityId,
            'charity_name' => $charityName,
            'resource_type' => 'Charity',
            'resource_id' => $charityId,
            'timestamp' => now()->toDateTimeString(),
        ], $adminId);
    }

    public static function logCharityCreated(int $userId, int $charityId, string $charityName): ?ActivityLog
    {
        return self::log('charity_created', [
            'charity_id' => $charityId,
            'charity_name' => $charityName,
            'resource_type' => 'Charity',
            'resource_id' => $charityId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logCharityUpdated(int $userId, int $charityId, string $charityName, array $changes = []): ?ActivityLog
    {
        return self::log('charity_updated', [
            'charity_id' => $charityId,
            'charity_name' => $charityName,
            'changes' => $changes,
            'resource_type' => 'Charity',
            'resource_id' => $charityId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logCharityRejected(int $adminId, int $charityId, string $charityName, ?string $reason = null): ?ActivityLog
    {
        return self::log('charity_rejected', [
            'charity_id' => $charityId,
            'charity_name' => $charityName,
            'reason' => $reason,
            'resource_type' => 'Charity',
            'resource_id' => $charityId,
            'timestamp' => now()->toDateTimeString(),
        ], $adminId);
    }

    public static function logCharitySuspended(int $adminId, int $charityId, string $charityName, ?string $reason = null): ?ActivityLog
    {
        return self::log('charity_suspended', [
            'charity_id' => $charityId,
            'charity_name' => $charityName,
            'reason' => $reason,
            'resource_type' => 'Charity',
            'resource_id' => $charityId,
            'timestamp' => now()->toDateTimeString(),
        ], $adminId);
    }

    public static function logCharityActivated(int $adminId, int $charityId, string $charityName): ?ActivityLog
    {
        return self::log('charity_activated', [
            'charity_id' => $charityId,
            'charity_name' => $charityName,
            'resource_type' => 'Charity',
            'resource_id' => $charityId,
            'timestamp' => now()->toDateTimeString(),
        ], $adminId);
    }

    /**
     * Log post/update actions
     */
    public static function logPostCreated(int $userId, int $postId, string $type): ?ActivityLog
    {
        return self::log('post_created', [
            'post_id' => $postId,
            'post_type' => $type,
            'resource_type' => 'Post',
            'resource_id' => $postId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logPostUpdated(int $userId, int $postId, string $type): ?ActivityLog
    {
        return self::log('post_updated', [
            'post_id' => $postId,
            'post_type' => $type,
            'resource_type' => 'Post',
            'resource_id' => $postId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logPostDeleted(int $userId, int $postId, string $type): ?ActivityLog
    {
        return self::log('post_deleted', [
            'post_id' => $postId,
            'post_type' => $type,
            'resource_type' => 'Post',
            'resource_id' => $postId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    /**
     * Log comment actions
     */
    /**
     * Log update actions (charity posts/updates)
     */
    public static function logUpdateCreated(int $userId, int $updateId, int $charityId): ?ActivityLog
    {
        return self::log('update_created', [
            'update_id' => $updateId,
            'charity_id' => $charityId,
            'resource_type' => 'Update',
            'resource_id' => $updateId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logUpdateUpdated(int $userId, int $updateId): ?ActivityLog
    {
        return self::log('update_updated', [
            'update_id' => $updateId,
            'resource_type' => 'Update',
            'resource_id' => $updateId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logUpdateDeleted(int $userId, int $updateId): ?ActivityLog
    {
        return self::log('update_deleted', [
            'update_id' => $updateId,
            'resource_type' => 'Update',
            'resource_id' => $updateId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logCommentCreated(int $userId, int $commentId, int $campaignId): ?ActivityLog
    {
        return self::log('comment_created', [
            'comment_id' => $commentId,
            'campaign_id' => $campaignId,
            'resource_type' => 'Comment',
            'resource_id' => $commentId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logCommentUpdated(int $userId, int $commentId): ?ActivityLog
    {
        return self::log('comment_updated', [
            'comment_id' => $commentId,
            'resource_type' => 'Comment',
            'resource_id' => $commentId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logCommentDeleted(int $userId, int $commentId): ?ActivityLog
    {
        return self::log('comment_deleted', [
            'comment_id' => $commentId,
            'resource_type' => 'Comment',
            'resource_id' => $commentId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    /**
     * Log follow actions
     */
    public static function logFollowAction(int $userId, int $charityId, string $action): ?ActivityLog
    {
        return self::log($action === 'follow' ? 'charity_followed' : 'charity_unfollowed', [
            'charity_id' => $charityId,
            'resource_type' => 'Charity',
            'resource_id' => $charityId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    /**
     * Log fund usage actions
     */
    public static function logFundUsageCreated(int $userId, int $fundUsageId, int $campaignId, float $amount): ?ActivityLog
    {
        return self::log('fund_usage_created', [
            'fund_usage_id' => $fundUsageId,
            'campaign_id' => $campaignId,
            'amount' => $amount,
            'resource_type' => 'FundUsage',
            'resource_id' => $fundUsageId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logFundUsageUpdated(int $userId, int $fundUsageId, int $campaignId, float $amount): ?ActivityLog
    {
        return self::log('fund_usage_updated', [
            'fund_usage_id' => $fundUsageId,
            'campaign_id' => $campaignId,
            'amount' => $amount,
            'resource_type' => 'FundUsage',
            'resource_id' => $fundUsageId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logFundUsageDeleted(int $userId, int $fundUsageId, int $campaignId): ?ActivityLog
    {
        return self::log('fund_usage_deleted', [
            'fund_usage_id' => $fundUsageId,
            'campaign_id' => $campaignId,
            'resource_type' => 'FundUsage',
            'resource_id' => $fundUsageId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    /**
     * Log refund actions
     */
    public static function logRefundRequested(int $userId, int $refundId, int $donationId): ?ActivityLog
    {
        return self::log('refund_requested', [
            'refund_id' => $refundId,
            'donation_id' => $donationId,
            'resource_type' => 'Refund',
            'resource_id' => $refundId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logRefundApproved(int $adminId, int $refundId, int $donationId, float $amount): ?ActivityLog
    {
        return self::log('refund_approved', [
            'refund_id' => $refundId,
            'donation_id' => $donationId,
            'amount' => $amount,
            'resource_type' => 'Refund',
            'resource_id' => $refundId,
            'timestamp' => now()->toDateTimeString(),
        ], $adminId);
    }

    public static function logRefundRejected(int $adminId, int $refundId, int $donationId, ?string $reason = null): ?ActivityLog
    {
        return self::log('refund_rejected', [
            'refund_id' => $refundId,
            'donation_id' => $donationId,
            'reason' => $reason,
            'resource_type' => 'Refund',
            'resource_id' => $refundId,
            'timestamp' => now()->toDateTimeString(),
        ], $adminId);
    }

    /**
     * Log account actions
     */
    public static function logAccountDeactivated(int $userId): ?ActivityLog
    {
        return self::log('account_deactivated', [
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logAccountReactivated(int $userId): ?ActivityLog
    {
        return self::log('account_reactivated', [
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logAccountDeleted(int $userId): ?ActivityLog
    {
        return self::log('account_deleted', [
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logEmailChanged(int $userId, string $oldEmail, string $newEmail): ?ActivityLog
    {
        return self::log('email_changed', [
            'old_email' => $oldEmail,
            'new_email' => $newEmail,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logEmailVerified(int $userId): ?ActivityLog
    {
        return self::log('email_verified', [
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    /**
     * Log document actions
     */
    public static function logDocumentUploaded(int $userId, int $documentId, int $charityId, string $docType): ?ActivityLog
    {
        return self::log('document_uploaded', [
            'document_id' => $documentId,
            'charity_id' => $charityId,
            'doc_type' => $docType,
            'resource_type' => 'Document',
            'resource_id' => $documentId,
            'timestamp' => now()->toDateTimeString(),
        ], $userId);
    }

    public static function logDocumentApproved(int $adminId, int $documentId, int $charityId): ?ActivityLog
    {
        return self::log('document_approved', [
            'document_id' => $documentId,
            'charity_id' => $charityId,
            'resource_type' => 'Document',
            'resource_id' => $documentId,
            'timestamp' => now()->toDateTimeString(),
        ], $adminId);
    }

    public static function logDocumentRejected(int $adminId, int $documentId, int $charityId, ?string $reason = null): ?ActivityLog
    {
        return self::log('document_rejected', [
            'document_id' => $documentId,
            'charity_id' => $charityId,
            'reason' => $reason,
            'resource_type' => 'Document',
            'resource_id' => $documentId,
            'timestamp' => now()->toDateTimeString(),
        ], $adminId);
    }

    /**
     * Log admin actions
     */
    public static function logUserSuspended(int $adminId, int $userId, string $userName, ?string $reason = null): ?ActivityLog
    {
        return self::log('user_suspended', [
            'suspended_user_id' => $userId,
            'suspended_user_name' => $userName,
            'reason' => $reason,
            'resource_type' => 'User',
            'resource_id' => $userId,
            'timestamp' => now()->toDateTimeString(),
        ], $adminId);
    }

    public static function logUserActivated(int $adminId, int $userId, string $userName): ?ActivityLog
    {
        return self::log('user_activated', [
            'activated_user_id' => $userId,
            'activated_user_name' => $userName,
            'resource_type' => 'User',
            'resource_id' => $userId,
            'timestamp' => now()->toDateTimeString(),
        ], $adminId);
    }

    public static function logReportReviewed(int $adminId, int $reportId, string $action): ?ActivityLog
    {
        return self::log('report_reviewed', [
            'report_id' => $reportId,
            'review_action' => $action,
            'resource_type' => 'Report',
            'resource_id' => $reportId,
            'timestamp' => now()->toDateTimeString(),
        ], $adminId);
    }

    /**
     * Get recent activities for a user
     */
    public static function getUserRecentActivities(int $userId, int $limit = 10)
    {
        return ActivityLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all activities for a specific action type
     */
    public static function getActivitiesByAction(string $action, int $limit = 50)
    {
        return ActivityLog::where('action', $action)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Clean up old logs (optional - for maintenance)
     */
    public static function cleanupOldLogs(int $daysToKeep = 90): int
    {
        return ActivityLog::where('created_at', '<', now()->subDays($daysToKeep))->delete();
    }
}
