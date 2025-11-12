<?php

/**
 * Send All Test Emails - Automated
 * 
 * Automatically sends all 9 test emails using real database data
 * 
 * Usage: php send_all_test_emails.php your@email.com
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\{Donation, RefundRequest};
use App\Mail\{
    DonationConfirmationMail,
    NewDonationAlertMail,
    DonationVerifiedMail,
    DonationRejectedMail,
    DonationAcknowledgmentMail,
    RefundRequestMail,
    RefundResponseMail
};

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get email from command line argument
$testEmail = $argv[1] ?? null;

if (!$testEmail || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
    echo "\n";
    echo "Usage: php send_all_test_emails.php your@email.com\n";
    echo "\n";
    echo "Example:\n";
    echo "  php send_all_test_emails.php bagunuaeron16@gmail.com\n";
    echo "\n";
    exit(1);
}

echo "\n";
echo "==============================================\n";
echo "   SENDING ALL 9 TEST EMAILS\n";
echo "==============================================\n\n";
echo "Test email address: {$testEmail}\n\n";

$emailsSent = 0;
$emailsFailed = 0;
$skipped = 0;

function sendEmail($mail, $to, $description) {
    global $emailsSent, $emailsFailed;
    
    try {
        Mail::to($to)->queue($mail);
        echo "✅ QUEUED: {$description}\n";
        $emailsSent++;
        return true;
    } catch (\Exception $e) {
        echo "❌ FAILED: {$description}\n";
        echo "   Error: {$e->getMessage()}\n";
        $emailsFailed++;
        return false;
    }
}

// Load data
$donations = Donation::with(['donor', 'charity', 'campaign'])->orderBy('created_at', 'desc')->get();
$refunds = RefundRequest::with(['donation.donor', 'donation.charity', 'donation.campaign', 'user'])
    ->orderBy('created_at', 'desc')->get();

echo "Loading database data...\n";
echo "  Donations found: {$donations->count()}\n";
echo "  Refunds found: {$refunds->count()}\n\n";

echo "Sending test emails...\n";
echo "-------------------------------------------\n\n";

// EMAIL 1: Donation Confirmation
$donation = $donations->first();
if ($donation) {
    sendEmail(
        new DonationConfirmationMail($donation),
        $testEmail,
        "1. Donation Confirmation (Donor)"
    );
} else {
    echo "⚠️  SKIPPED: 1. Donation Confirmation (no data)\n";
    $skipped++;
}

// EMAIL 2: New Donation Alert
if ($donation) {
    sendEmail(
        new NewDonationAlertMail($donation),
        $testEmail,
        "2. New Donation Alert (Charity)"
    );
} else {
    echo "⚠️  SKIPPED: 2. New Donation Alert (no data)\n";
    $skipped++;
}

// EMAIL 3: Donation Verified
$completedDonation = $donations->where('status', 'completed')->first();
if ($completedDonation) {
    sendEmail(
        new DonationVerifiedMail($completedDonation),
        $testEmail,
        "3. Donation Verified (Donor)"
    );
} else {
    echo "⚠️  SKIPPED: 3. Donation Verified (no data)\n";
    $skipped++;
}

// EMAIL 4: Acknowledgment Letter
if ($completedDonation) {
    sendEmail(
        new DonationAcknowledgmentMail($completedDonation),
        $testEmail,
        "4. Acknowledgment Letter with PDF (Donor)"
    );
} else {
    echo "⚠️  SKIPPED: 4. Acknowledgment Letter (no data)\n";
    $skipped++;
}

// EMAIL 5: Donation Rejected
if ($donation) {
    sendEmail(
        new DonationRejectedMail($donation, "The proof of payment was unclear. Please resubmit a clearer photo."),
        $testEmail,
        "5. Donation Rejected (Donor)"
    );
} else {
    echo "⚠️  SKIPPED: 5. Donation Rejected (no data)\n";
    $skipped++;
}

// EMAIL 6: Refund Request Confirmation
$refund = $refunds->first();
if ($refund && $refund->user && $refund->donation) {
    sendEmail(
        new RefundRequestMail($refund->user, $refund->donation, $refund, 'donor'),
        $testEmail,
        "6. Refund Request Confirmation (Donor)"
    );
} else {
    echo "⚠️  SKIPPED: 6. Refund Request Confirmation (no data)\n";
    $skipped++;
}

// EMAIL 7: Refund Request Alert
if ($refund && $refund->donation && $refund->donation->charity && $refund->donation->charity->owner) {
    sendEmail(
        new RefundRequestMail(
            $refund->donation->charity->owner,
            $refund->donation,
            $refund,
            'charity'
        ),
        $testEmail,
        "7. Refund Request Alert (Charity)"
    );
} else {
    echo "⚠️  SKIPPED: 7. Refund Request Alert (no data)\n";
    $skipped++;
}

// EMAIL 8: Refund Approved
$approvedRefund = $refunds->where('status', 'approved')->first();
if ($approvedRefund && $approvedRefund->user && $approvedRefund->donation) {
    sendEmail(
        new RefundResponseMail($approvedRefund->user, $approvedRefund->donation, $approvedRefund),
        $testEmail,
        "8. Refund Approved (Donor)"
    );
} else {
    echo "⚠️  SKIPPED: 8. Refund Approved (no data)\n";
    $skipped++;
}

// EMAIL 9: Refund Denied
$deniedRefund = $refunds->where('status', 'denied')->first();
if ($deniedRefund && $deniedRefund->user && $deniedRefund->donation) {
    sendEmail(
        new RefundResponseMail($deniedRefund->user, $deniedRefund->donation, $deniedRefund),
        $testEmail,
        "9. Refund Denied (Donor)"
    );
} else {
    echo "⚠️  SKIPPED: 9. Refund Denied (no data)\n";
    $skipped++;
}

// Summary
echo "\n==============================================\n";
echo "SUMMARY\n";
echo "==============================================\n\n";

echo "Test email: {$testEmail}\n";
echo "✅ Queued: {$emailsSent}\n";
echo "❌ Failed: {$emailsFailed}\n";
echo "⚠️  Skipped: {$skipped}\n\n";

if ($emailsSent > 0) {
    echo "✅ {$emailsSent} EMAIL(S) HAVE BEEN QUEUED!\n\n";
    
    echo "==============================================\n";
    echo "NEXT STEPS\n";
    echo "==============================================\n\n";
    
    echo "OPTION 1: Process queue with worker (recommended)\n";
    echo "-------------------------------------------\n";
    echo "Open another terminal and run:\n";
    echo "  cd ..\n";
    echo "  .\\start-email-queue.ps1\n\n";
    echo "Emails will be processed automatically.\n\n";
    
    echo "OPTION 2: Process queue manually (quick test)\n";
    echo "-------------------------------------------\n";
    echo "  php artisan queue:work --once\n\n";
    echo "This processes one batch of emails.\n\n";
    
    echo "OPTION 3: Check queued jobs\n";
    echo "-------------------------------------------\n";
    echo "  php artisan queue:monitor\n\n";
    
    echo "==============================================\n";
    echo "CHECK YOUR INBOX\n";
    echo "==============================================\n\n";
    
    echo "Email address: {$testEmail}\n";
    echo "Expected emails: {$emailsSent}\n\n";
    
    echo "Tips:\n";
    echo "  - Check spam/junk folder\n";
    echo "  - Allow a few minutes for delivery\n";
    echo "  - Gmail may group emails as conversation\n\n";
    
} else {
    echo "❌ NO EMAILS WERE SENT\n\n";
    echo "Possible reasons:\n";
    echo "  - No donations or refunds in database\n";
    echo "  - Database connection issue\n";
    echo "  - Mail configuration error\n\n";
}

echo "==============================================\n\n";
