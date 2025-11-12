<?php

/**
 * Send Test Emails - Real Email Testing
 * 
 * This script finds real donations and refunds in the database
 * and sends actual test emails for all 9 email types.
 * 
 * Run: php send_test_emails.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\{Donation, RefundRequest, User, Charity, Campaign};
use App\Mail\{
    DonationConfirmationMail,
    NewDonationAlertMail,
    DonationVerifiedMail,
    DonationRejectedMail,
    DonationAcknowledgmentMail,
    RefundRequestMail,
    RefundResponseMail
};

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "==============================================\n";
echo "   SEND TEST EMAILS - ALL 9 EMAIL TYPES\n";
echo "==============================================\n\n";

$emailsSent = 0;
$emailsFailed = 0;

// Helper function to send email
function sendTestEmail($emailClass, $to, $description) {
    global $emailsSent, $emailsFailed;
    
    try {
        Mail::to($to)->queue($emailClass);
        echo "✅ QUEUED: {$description}\n";
        echo "   To: {$to}\n\n";
        $emailsSent++;
        return true;
    } catch (\Exception $e) {
        echo "❌ FAILED: {$description}\n";
        echo "   Error: {$e->getMessage()}\n\n";
        $emailsFailed++;
        return false;
    }
}

// Step 1: Check for donations
echo "STEP 1: Checking for donations in database\n";
echo "-------------------------------------------\n";

$donations = Donation::with(['donor', 'charity', 'campaign'])
    ->whereNotNull('donor_id')
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();

if ($donations->isEmpty()) {
    echo "⚠️  No donations found in database.\n";
    echo "   Create a donation first to test emails.\n\n";
} else {
    echo "✓ Found {$donations->count()} donation(s)\n\n";
    
    foreach ($donations as $donation) {
        echo "  Donation #{$donation->id}\n";
        echo "    Donor: " . ($donation->donor ? $donation->donor->name : 'N/A') . "\n";
        echo "    Amount: ₱" . number_format($donation->amount, 2) . "\n";
        echo "    Status: {$donation->status}\n";
        echo "    Campaign: " . ($donation->campaign ? $donation->campaign->title : 'N/A') . "\n";
        echo "\n";
    }
}

// Step 2: Check for refunds
echo "STEP 2: Checking for refund requests\n";
echo "-------------------------------------------\n";

$refunds = RefundRequest::with(['donation.donor', 'donation.charity', 'donation.campaign'])
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();

if ($refunds->isEmpty()) {
    echo "⚠️  No refund requests found in database.\n\n";
} else {
    echo "✓ Found {$refunds->count()} refund request(s)\n\n";
    
    foreach ($refunds as $refund) {
        echo "  Refund #{$refund->id}\n";
        echo "    Status: {$refund->status}\n";
        echo "    Amount: ₱" . number_format($refund->refund_amount, 2) . "\n";
        echo "    Donor: " . ($refund->user ? $refund->user->name : 'N/A') . "\n";
        echo "\n";
    }
}

// Step 3: Ask user for test email address
echo "==============================================\n";
echo "STEP 3: Email Address for Testing\n";
echo "==============================================\n\n";

echo "Enter your email address to receive test emails: ";
$testEmail = trim(fgets(STDIN));

if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Invalid email address. Exiting.\n\n";
    exit(1);
}

echo "\n✓ Test emails will be sent to: {$testEmail}\n\n";

echo "==============================================\n";
echo "STEP 4: Sending Test Emails\n";
echo "==============================================\n\n";

// EMAIL 1: Donation Confirmation (to donor)
echo "TEST 1: Donation Confirmation Email (Donor)\n";
echo "-------------------------------------------\n";

if (!$donations->isEmpty()) {
    $donation = $donations->first();
    sendTestEmail(
        new DonationConfirmationMail($donation),
        $testEmail,
        "Donation Confirmation - When donor makes donation"
    );
} else {
    echo "⚠️  SKIPPED: No donations available\n\n";
}

// EMAIL 2: New Donation Alert (to charity)
echo "TEST 2: New Donation Alert Email (Charity)\n";
echo "-------------------------------------------\n";

if (!$donations->isEmpty()) {
    $donation = $donations->first();
    sendTestEmail(
        new NewDonationAlertMail($donation),
        $testEmail,
        "New Donation Alert - Charity receives when donation made"
    );
} else {
    echo "⚠️  SKIPPED: No donations available\n\n";
}

// EMAIL 3: Donation Verified (to donor)
echo "TEST 3: Donation Verified Email (Donor)\n";
echo "-------------------------------------------\n";

$verifiedDonation = $donations->where('status', 'completed')->first();
if ($verifiedDonation) {
    sendTestEmail(
        new DonationVerifiedMail($verifiedDonation),
        $testEmail,
        "Donation Verified - When charity approves donation"
    );
} else {
    echo "⚠️  SKIPPED: No completed donations found\n\n";
}

// EMAIL 4: Donation Acknowledgment Letter (to donor)
echo "TEST 4: Donation Acknowledgment Letter (Donor)\n";
echo "-------------------------------------------\n";

if ($verifiedDonation) {
    sendTestEmail(
        new DonationAcknowledgmentMail($verifiedDonation),
        $testEmail,
        "Acknowledgment Letter (PDF) - After donation approved"
    );
} else {
    echo "⚠️  SKIPPED: No completed donations found\n\n";
}

// EMAIL 5: Donation Rejected (to donor)
echo "TEST 5: Donation Rejected Email (Donor)\n";
echo "-------------------------------------------\n";

$rejectedDonation = $donations->where('status', 'rejected')->first();
if ($rejectedDonation) {
    sendTestEmail(
        new DonationRejectedMail($rejectedDonation, "Test rejection - proof unclear"),
        $testEmail,
        "Donation Rejected - When charity rejects donation"
    );
} else {
    // Use any donation for testing
    if (!$donations->isEmpty()) {
        $donation = $donations->first();
        sendTestEmail(
            new DonationRejectedMail($donation, "Test rejection - proof unclear"),
            $testEmail,
            "Donation Rejected - When charity rejects donation (SIMULATION)"
        );
    } else {
        echo "⚠️  SKIPPED: No donations available\n\n";
    }
}

// EMAIL 6: Refund Request Confirmation (to donor)
echo "TEST 6: Refund Request Confirmation (Donor)\n";
echo "-------------------------------------------\n";

if (!$refunds->isEmpty()) {
    $refund = $refunds->first();
    if ($refund->user && $refund->donation) {
        sendTestEmail(
            new RefundRequestMail($refund->user, $refund->donation, $refund, 'donor'),
            $testEmail,
            "Refund Request Confirmation - When donor requests refund"
        );
    } else {
        echo "⚠️  SKIPPED: Refund data incomplete\n\n";
    }
} else {
    echo "⚠️  SKIPPED: No refund requests found\n\n";
}

// EMAIL 7: Refund Request Alert (to charity)
echo "TEST 7: Refund Request Alert (Charity)\n";
echo "-------------------------------------------\n";

if (!$refunds->isEmpty()) {
    $refund = $refunds->first();
    if ($refund->donation && $refund->donation->charity && $refund->donation->charity->owner) {
        sendTestEmail(
            new RefundRequestMail(
                $refund->donation->charity->owner,
                $refund->donation,
                $refund,
                'charity'
            ),
            $testEmail,
            "Refund Request Alert - Charity receives when refund requested"
        );
    } else {
        echo "⚠️  SKIPPED: Refund/charity data incomplete\n\n";
    }
} else {
    echo "⚠️  SKIPPED: No refund requests found\n\n";
}

// EMAIL 8: Refund Approved (to donor)
echo "TEST 8: Refund Approved Email (Donor)\n";
echo "-------------------------------------------\n";

$approvedRefund = $refunds->where('status', 'approved')->first();
if ($approvedRefund && $approvedRefund->user && $approvedRefund->donation) {
    sendTestEmail(
        new RefundResponseMail($approvedRefund->user, $approvedRefund->donation, $approvedRefund),
        $testEmail,
        "Refund Approved - When charity approves refund"
    );
} else {
    echo "⚠️  SKIPPED: No approved refunds found\n\n";
}

// EMAIL 9: Refund Denied (to donor)
echo "TEST 9: Refund Denied Email (Donor)\n";
echo "-------------------------------------------\n";

$deniedRefund = $refunds->where('status', 'denied')->first();
if ($deniedRefund && $deniedRefund->user && $deniedRefund->donation) {
    sendTestEmail(
        new RefundResponseMail($deniedRefund->user, $deniedRefund->donation, $deniedRefund),
        $testEmail,
        "Refund Denied - When charity denies refund"
    );
} else {
    echo "⚠️  SKIPPED: No denied refunds found\n\n";
}

// Summary
echo "==============================================\n";
echo "SUMMARY\n";
echo "==============================================\n\n";

echo "Test email address: {$testEmail}\n";
echo "Emails queued: {$emailsSent}\n";
echo "Emails failed: {$emailsFailed}\n\n";

if ($emailsSent > 0) {
    echo "✅ {$emailsSent} email(s) have been queued!\n\n";
    
    echo "NEXT STEPS:\n";
    echo "-------------------------------------------\n";
    echo "1. Make sure queue worker is running:\n";
    echo "   cd ..\n";
    echo "   .\\start-email-queue.ps1\n\n";
    
    echo "2. Process the queue:\n";
    echo "   php artisan queue:work --once\n\n";
    
    echo "3. Check your email inbox:\n";
    echo "   Email: {$testEmail}\n";
    echo "   Check spam folder if not in inbox\n\n";
    
    echo "4. View queued jobs:\n";
    echo "   php artisan queue:monitor\n\n";
} else {
    echo "⚠️  No emails were sent.\n";
    echo "   Reason: No donations or refunds in database\n\n";
    
    echo "TO CREATE TEST DATA:\n";
    echo "-------------------------------------------\n";
    echo "1. Login to the app as a donor\n";
    echo "2. Make a donation to any campaign\n";
    echo "3. Run this script again\n\n";
}

echo "==============================================\n\n";
