<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Donation;
use App\Models\Campaign;
use App\Mail\EmailVerifiedMail;
use App\Mail\PasswordChangedMail;
use App\Mail\AccountDeactivatedMail;
use App\Mail\DonationVerifiedMail;
use App\Mail\DonationRejectedMail;
use App\Jobs\SendCampaignCompletedEmails;
use App\Jobs\SendNewCampaignNotifications;
use Illuminate\Support\Facades\Mail;

echo "========================================\n";
echo "EMAIL SYSTEM TESTING - CharityHub\n";
echo "========================================\n\n";

$testEmail = 'charityhub25@gmail.com';
$results = [];

// Test 1: Email Verified Mail
echo "[1/7] Testing EmailVerifiedMail...\n";
try {
    $user = User::first();
    if ($user) {
        Mail::to($testEmail)->queue(new EmailVerifiedMail($user));
        echo "✓ EmailVerifiedMail queued successfully\n";
        echo "  - User: {$user->name} ({$user->email})\n";
        echo "  - Role: {$user->role}\n";
        $results['EmailVerifiedMail'] = 'PASS';
    } else {
        echo "✗ No users found in database\n";
        $results['EmailVerifiedMail'] = 'FAIL - No users';
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $results['EmailVerifiedMail'] = 'FAIL - ' . $e->getMessage();
}
echo "\n";

// Test 2: Password Changed Mail
echo "[2/7] Testing PasswordChangedMail...\n";
try {
    $user = User::first();
    if ($user) {
        Mail::to($testEmail)->queue(new PasswordChangedMail($user, '127.0.0.1', 'Mozilla/5.0 (Test Browser)'));
        echo "✓ PasswordChangedMail queued successfully\n";
        echo "  - User: {$user->name}\n";
        echo "  - IP: 127.0.0.1\n";
        $results['PasswordChangedMail'] = 'PASS';
    } else {
        echo "✗ No users found\n";
        $results['PasswordChangedMail'] = 'FAIL - No users';
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $results['PasswordChangedMail'] = 'FAIL - ' . $e->getMessage();
}
echo "\n";

// Test 3: Account Deactivated Mail
echo "[3/7] Testing AccountDeactivatedMail...\n";
try {
    $user = User::first();
    if ($user) {
        Mail::to($testEmail)->queue(new AccountDeactivatedMail($user));
        echo "✓ AccountDeactivatedMail queued successfully\n";
        echo "  - User: {$user->name}\n";
        echo "  - Role: {$user->role}\n";
        $results['AccountDeactivatedMail'] = 'PASS';
    } else {
        echo "✗ No users found\n";
        $results['AccountDeactivatedMail'] = 'FAIL - No users';
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $results['AccountDeactivatedMail'] = 'FAIL - ' . $e->getMessage();
}
echo "\n";

// Test 4: Donation Verified Mail
echo "[4/7] Testing DonationVerifiedMail...\n";
try {
    $donation = Donation::with(['donor', 'charity', 'campaign'])->first();
    if ($donation) {
        Mail::to($testEmail)->queue(new DonationVerifiedMail($donation));
        echo "✓ DonationVerifiedMail queued successfully\n";
        echo "  - Amount: ₱" . number_format($donation->amount, 2) . "\n";
        echo "  - Campaign: " . ($donation->campaign->title ?? 'N/A') . "\n";
        $results['DonationVerifiedMail'] = 'PASS';
    } else {
        echo "⚠ No donations found - creating test scenario\n";
        $results['DonationVerifiedMail'] = 'SKIP - No donations';
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $results['DonationVerifiedMail'] = 'FAIL - ' . $e->getMessage();
}
echo "\n";

// Test 5: Donation Rejected Mail
echo "[5/7] Testing DonationRejectedMail...\n";
try {
    $donation = Donation::with(['donor', 'charity', 'campaign'])->first();
    if ($donation) {
        Mail::to($testEmail)->queue(new DonationRejectedMail($donation, 'Proof of payment is unclear. Please upload a clearer image.'));
        echo "✓ DonationRejectedMail queued successfully\n";
        echo "  - Amount: ₱" . number_format($donation->amount, 2) . "\n";
        echo "  - Reason: Proof unclear\n";
        $results['DonationRejectedMail'] = 'PASS';
    } else {
        echo "⚠ No donations found\n";
        $results['DonationRejectedMail'] = 'SKIP - No donations';
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $results['DonationRejectedMail'] = 'FAIL - ' . $e->getMessage();
}
echo "\n";

// Test 6: Campaign Completed Emails (Batch Job)
echo "[6/7] Testing SendCampaignCompletedEmails Job...\n";
try {
    $campaign = Campaign::with('charity')->whereHas('donations', function($q) {
        $q->where('status', 'completed');
    })->first();
    
    if ($campaign) {
        dispatch(new SendCampaignCompletedEmails($campaign));
        echo "✓ SendCampaignCompletedEmails job dispatched successfully\n";
        echo "  - Campaign: {$campaign->title}\n";
        echo "  - Charity: " . ($campaign->charity->name ?? 'N/A') . "\n";
        $results['SendCampaignCompletedEmails'] = 'PASS';
    } else {
        echo "⚠ No campaigns with donations found\n";
        $results['SendCampaignCompletedEmails'] = 'SKIP - No campaigns';
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $results['SendCampaignCompletedEmails'] = 'FAIL - ' . $e->getMessage();
}
echo "\n";

// Test 7: New Campaign Notifications (Batch Job)
echo "[7/7] Testing SendNewCampaignNotifications Job...\n";
try {
    $campaign = Campaign::with('charity')->first();
    
    if ($campaign) {
        dispatch(new SendNewCampaignNotifications($campaign));
        echo "✓ SendNewCampaignNotifications job dispatched successfully\n";
        echo "  - Campaign: {$campaign->title}\n";
        echo "  - Charity: " . ($campaign->charity->name ?? 'N/A') . "\n";
        $results['SendNewCampaignNotifications'] = 'PASS';
    } else {
        echo "⚠ No campaigns found\n";
        $results['SendNewCampaignNotifications'] = 'SKIP - No campaigns';
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $results['SendNewCampaignNotifications'] = 'FAIL - ' . $e->getMessage();
}
echo "\n";

// Summary
echo "========================================\n";
echo "TEST SUMMARY\n";
echo "========================================\n";
$passed = 0;
$failed = 0;
$skipped = 0;

foreach ($results as $test => $result) {
    $status = str_starts_with($result, 'PASS') ? '✓' : (str_starts_with($result, 'SKIP') ? '⚠' : '✗');
    echo "{$status} {$test}: {$result}\n";
    
    if (str_starts_with($result, 'PASS')) $passed++;
    elseif (str_starts_with($result, 'SKIP')) $skipped++;
    else $failed++;
}

echo "\n";
echo "PASSED: {$passed}\n";
echo "FAILED: {$failed}\n";
echo "SKIPPED: {$skipped}\n";
echo "\n";

// Check jobs table
echo "========================================\n";
echo "QUEUE STATUS\n";
echo "========================================\n";
$jobCount = DB::table('jobs')->count();
echo "Jobs in queue: {$jobCount}\n";

if ($jobCount > 0) {
    echo "\n⚠ Queue worker needs to be running to process these emails!\n";
    echo "Run: php artisan queue:work\n";
}

echo "\nAll emails will be sent to: {$testEmail}\n";
echo "========================================\n";
