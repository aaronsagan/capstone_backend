<?php

/**
 * Check Email Data - View donations and refunds for email testing
 * 
 * Run: php check_email_data.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\{Donation, RefundRequest};

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "==============================================\n";
echo "   CHECKING DATA FOR EMAIL TESTING\n";
echo "==============================================\n\n";

// Check donations
echo "DONATIONS IN DATABASE:\n";
echo "-------------------------------------------\n";

$donations = Donation::with(['donor', 'charity', 'campaign'])
    ->orderBy('created_at', 'desc')
    ->get();

if ($donations->isEmpty()) {
    echo "❌ No donations found\n\n";
    echo "CREATE A DONATION:\n";
    echo "  1. Login as donor\n";
    echo "  2. Make donation to a campaign\n";
    echo "  3. Run this script again\n\n";
} else {
    echo "✓ Found {$donations->count()} donation(s)\n\n";
    
    $statusCounts = [
        'pending' => 0,
        'completed' => 0,
        'rejected' => 0,
        'refunded' => 0
    ];
    
    foreach ($donations as $donation) {
        $statusCounts[$donation->status] = ($statusCounts[$donation->status] ?? 0) + 1;
        
        echo "  Donation #{$donation->id}\n";
        echo "    Status: {$donation->status}\n";
        echo "    Amount: ₱" . number_format($donation->amount, 2) . "\n";
        
        if ($donation->donor) {
            echo "    Donor: {$donation->donor->name} ({$donation->donor->email})\n";
        } else {
            echo "    Donor: Guest\n";
        }
        
        if ($donation->charity) {
            echo "    Charity: {$donation->charity->name}\n";
            if ($donation->charity->owner) {
                echo "    Charity Email: {$donation->charity->owner->email}\n";
            }
        }
        
        if ($donation->campaign) {
            echo "    Campaign: {$donation->campaign->title}\n";
        }
        
        echo "    Date: {$donation->created_at->format('M d, Y')}\n";
        echo "\n";
    }
    
    echo "STATUS BREAKDOWN:\n";
    echo "  Pending: {$statusCounts['pending']}\n";
    echo "  Completed: {$statusCounts['completed']}\n";
    echo "  Rejected: {$statusCounts['rejected']}\n";
    echo "  Refunded: {$statusCounts['refunded']}\n\n";
}

// Check refunds
echo "==============================================\n";
echo "REFUND REQUESTS IN DATABASE:\n";
echo "-------------------------------------------\n";

$refunds = RefundRequest::with(['donation.donor', 'donation.charity', 'donation.campaign', 'user'])
    ->orderBy('created_at', 'desc')
    ->get();

if ($refunds->isEmpty()) {
    echo "❌ No refund requests found\n\n";
    echo "CREATE A REFUND REQUEST:\n";
    echo "  1. Login as donor\n";
    echo "  2. Go to donation history\n";
    echo "  3. Request refund for a completed donation\n";
    echo "  4. Run this script again\n\n";
} else {
    echo "✓ Found {$refunds->count()} refund request(s)\n\n";
    
    $refundStatusCounts = [
        'pending' => 0,
        'approved' => 0,
        'denied' => 0
    ];
    
    foreach ($refunds as $refund) {
        $refundStatusCounts[$refund->status] = ($refundStatusCounts[$refund->status] ?? 0) + 1;
        
        echo "  Refund #{$refund->id}\n";
        echo "    Status: {$refund->status}\n";
        echo "    Amount: ₱" . number_format($refund->refund_amount, 2) . "\n";
        
        if ($refund->user) {
            echo "    Donor: {$refund->user->name} ({$refund->user->email})\n";
        }
        
        if ($refund->donation && $refund->donation->campaign) {
            echo "    Campaign: {$refund->donation->campaign->title}\n";
        }
        
        echo "    Reason: " . substr($refund->reason, 0, 50) . "...\n";
        echo "    Date: {$refund->created_at->format('M d, Y')}\n";
        echo "\n";
    }
    
    echo "STATUS BREAKDOWN:\n";
    echo "  Pending: {$refundStatusCounts['pending']}\n";
    echo "  Approved: {$refundStatusCounts['approved']}\n";
    echo "  Denied: {$refundStatusCounts['denied']}\n\n";
}

// Summary and recommendations
echo "==============================================\n";
echo "EMAIL TESTING READINESS\n";
echo "==============================================\n\n";

$canTestEmails = [];
$cannotTestEmails = [];

// Check each email type
if (!$donations->isEmpty()) {
    $canTestEmails[] = "✅ EMAIL 1: Donation Confirmation";
    $canTestEmails[] = "✅ EMAIL 2: New Donation Alert";
} else {
    $cannotTestEmails[] = "❌ EMAIL 1: Donation Confirmation (no donations)";
    $cannotTestEmails[] = "❌ EMAIL 2: New Donation Alert (no donations)";
}

$completedDonation = $donations->where('status', 'completed')->first();
if ($completedDonation) {
    $canTestEmails[] = "✅ EMAIL 3: Donation Verified";
    $canTestEmails[] = "✅ EMAIL 4: Acknowledgment Letter";
} else {
    $cannotTestEmails[] = "❌ EMAIL 3: Donation Verified (no completed donations)";
    $cannotTestEmails[] = "❌ EMAIL 4: Acknowledgment Letter (no completed donations)";
}

$rejectedDonation = $donations->where('status', 'rejected')->first();
if ($rejectedDonation || !$donations->isEmpty()) {
    $canTestEmails[] = "✅ EMAIL 5: Donation Rejected";
} else {
    $cannotTestEmails[] = "❌ EMAIL 5: Donation Rejected (no donations)";
}

if (!$refunds->isEmpty()) {
    $canTestEmails[] = "✅ EMAIL 6: Refund Request (Donor)";
    $canTestEmails[] = "✅ EMAIL 7: Refund Request (Charity)";
} else {
    $cannotTestEmails[] = "❌ EMAIL 6: Refund Request (Donor) (no refunds)";
    $cannotTestEmails[] = "❌ EMAIL 7: Refund Request (Charity) (no refunds)";
}

$approvedRefund = $refunds->where('status', 'approved')->first();
if ($approvedRefund) {
    $canTestEmails[] = "✅ EMAIL 8: Refund Approved";
} else {
    $cannotTestEmails[] = "❌ EMAIL 8: Refund Approved (no approved refunds)";
}

$deniedRefund = $refunds->where('status', 'denied')->first();
if ($deniedRefund) {
    $canTestEmails[] = "✅ EMAIL 9: Refund Denied";
} else {
    $cannotTestEmails[] = "❌ EMAIL 9: Refund Denied (no denied refunds)";
}

foreach ($canTestEmails as $email) {
    echo $email . "\n";
}

foreach ($cannotTestEmails as $email) {
    echo $email . "\n";
}

echo "\n";

$readyCount = count($canTestEmails);
$totalCount = count($canTestEmails) + count($cannotTestEmails);

echo "READY: {$readyCount}/{$totalCount} email types can be tested\n\n";

if ($readyCount === $totalCount) {
    echo "🎉 ALL EMAIL TYPES READY TO TEST!\n\n";
    echo "RUN TEST:\n";
    echo "  php send_test_emails.php\n\n";
} else {
    echo "⚠️  SOME EMAIL TYPES CANNOT BE TESTED\n\n";
    
    if ($donations->isEmpty()) {
        echo "CREATE DONATIONS:\n";
        echo "  1. Login as donor to the app\n";
        echo "  2. Make 2-3 donations to different campaigns\n";
        echo "  3. Login as charity\n";
        echo "  4. Approve one donation\n";
        echo "  5. Reject one donation\n\n";
    }
    
    if ($refunds->isEmpty()) {
        echo "CREATE REFUND REQUESTS:\n";
        echo "  1. Login as donor\n";
        echo "  2. Request refund for a completed donation\n";
        echo "  3. Login as charity\n";
        echo "  4. Approve one refund request\n";
        echo "  5. Deny another refund request\n\n";
    }
    
    echo "Then run this script again to check readiness.\n\n";
}

echo "==============================================\n\n";
