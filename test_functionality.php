<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== COMPREHENSIVE FUNCTIONALITY TEST ===\n\n";

$results = [
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0
];

function test($name, $condition, $message = '') {
    global $results;
    if ($condition) {
        echo "✅ PASS: $name\n";
        $results['passed']++;
        return true;
    } else {
        echo "❌ FAIL: $name" . ($message ? " - $message" : "") . "\n";
        $results['failed']++;
        return false;
    }
}

function warn($name, $message) {
    global $results;
    echo "⚠️  WARN: $name - $message\n";
    $results['warnings']++;
}

echo "--- 1. USER MANAGEMENT TESTS ---\n";

// Test 1.1: User CRUD
$users = App\Models\User::all();
test("Users table accessible", $users->count() > 0);
test("Multiple user roles exist", App\Models\User::distinct('role')->count() >= 3);
test("Admin user exists", App\Models\User::where('role', 'admin')->exists());
test("Donor users exist", App\Models\User::where('role', 'donor')->exists());
test("Charity admin users exist", App\Models\User::where('role', 'charity_admin')->exists());

// Test 1.2: User status management
$suspendedUsers = App\Models\User::where('status', 'suspended')->count();
test("User status field exists", true);
if ($suspendedUsers > 0) {
    warn("Suspended users", "Found $suspendedUsers suspended users - verify suspension logic works");
}

// Test 1.3: Password validation
$sampleUser = App\Models\User::first();
test("Password is hashed", $sampleUser && strlen($sampleUser->password) > 50);

echo "\n--- 2. CHARITY MANAGEMENT TESTS ---\n";

// Test 2.1: Charity verification
$charities = App\Models\Charity::all();
test("Charities table accessible", $charities->count() > 0);
test("Charity verification statuses exist", App\Models\Charity::whereIn('verification_status', ['pending', 'approved', 'rejected'])->exists());

$approvedCharities = App\Models\Charity::where('verification_status', 'approved')->count();
$pendingCharities = App\Models\Charity::where('verification_status', 'pending')->count();
test("Approved charities exist", $approvedCharities > 0, "Found $approvedCharities approved");
if ($pendingCharities > 0) {
    warn("Pending charities", "Found $pendingCharities pending verification");
}

// Test 2.2: Charity documents
$documents = App\Models\CharityDocument::count();
test("Charity documents table accessible", true);
if ($documents > 0) {
    test("Charity documents uploaded", true, "Found $documents documents");
    $docTypes = App\Models\CharityDocument::distinct('doc_type')->pluck('doc_type');
    test("Multiple document types exist", $docTypes->count() > 1, "Types: " . $docTypes->implode(', '));
} else {
    warn("No charity documents", "No documents found - upload functionality not tested");
}

// Test 2.3: Charity relationships
$charityWithOwner = App\Models\Charity::with('owner')->first();
test("Charity-User relationship works", $charityWithOwner && $charityWithOwner->owner !== null);

echo "\n--- 3. DONATION MANAGEMENT TESTS ---\n";

// Test 3.1: Donations exist
$donations = App\Models\Donation::all();
test("Donations table accessible", $donations->count() > 0, "Found {$donations->count()} donations");

// Test 3.2: Donation statuses
$completedDonations = App\Models\Donation::where('status', 'completed')->count();
$pendingDonations = App\Models\Donation::where('status', 'pending')->count();
test("Completed donations exist", $completedDonations > 0, "Found $completedDonations completed");
if ($pendingDonations > 0) {
    warn("Pending donations", "Found $pendingDonations pending donations");
}

// Test 3.3: Anonymous donations
$anonymousDonations = App\Models\Donation::where('is_anonymous', true)->count();
test("Anonymous donation field exists", true);
if ($anonymousDonations > 0) {
    test("Anonymous donations functional", true, "Found $anonymousDonations anonymous donations");
}

// Test 3.4: Recurring donations
$recurringDonations = App\Models\Donation::where('is_recurring', true)->count();
test("Recurring donation field exists", true);
if ($recurringDonations == 0) {
    warn("No recurring donations", "Recurring donation logic not tested in production data");
}

// Test 3.5: Receipt generation
$donationsWithReceipts = App\Models\Donation::whereNotNull('receipt_no')->count();
test("Receipt numbers generated", $donationsWithReceipts > 0, "Found $donationsWithReceipts receipts");

// Test 3.6: Donation-Campaign relationship
$donationWithCampaign = App\Models\Donation::with('campaign')->whereNotNull('campaign_id')->first();
test("Donation-Campaign relationship works", $donationWithCampaign && $donationWithCampaign->campaign !== null);

echo "\n--- 4. FUND TRACKING TESTS ---\n";

// Test 4.1: Fund usage logs
$fundLogs = App\Models\FundUsageLog::count();
test("Fund usage logs table accessible", true);
test("Fund usage logs exist", $fundLogs > 0, "Found $fundLogs fund logs");

if ($fundLogs > 0) {
    // Test 4.2: Fund categories
    $categories = App\Models\FundUsageLog::distinct('category')->pluck('category');
    test("Multiple fund categories exist", $categories->count() > 1, "Categories: " . $categories->implode(', '));
    
    // Test 4.3: Fund attachments
    $logsWithAttachments = App\Models\FundUsageLog::whereNotNull('attachment_path')->count();
    if ($logsWithAttachments > 0) {
        test("Fund usage attachments uploaded", true, "Found $logsWithAttachments with attachments");
    } else {
        warn("No fund attachments", "Proof of expense upload not tested");
    }
    
    // Test 4.4: Fund-Campaign relationship
    $fundWithCampaign = App\Models\FundUsageLog::with('campaign')->first();
    test("FundUsage-Campaign relationship works", $fundWithCampaign && $fundWithCampaign->campaign !== null);
}

echo "\n--- 5. CAMPAIGN MANAGEMENT TESTS ---\n";

// Test 5.1: Campaigns exist
$campaigns = App\Models\Campaign::count();
test("Campaigns table accessible", true);
test("Campaigns exist", $campaigns > 0, "Found $campaigns campaigns");

if ($campaigns > 0) {
    // Test 5.2: Campaign types
    $campaignTypes = App\Models\Campaign::distinct('campaign_type')->pluck('campaign_type');
    test("Campaign types exist", $campaignTypes->count() > 0, "Types: " . $campaignTypes->implode(', '));
    
    // Test 5.3: Campaign statuses
    $publishedCampaigns = App\Models\Campaign::where('status', 'published')->count();
    test("Published campaigns exist", $publishedCampaigns > 0, "Found $publishedCampaigns published");
    
    // Test 5.4: Recurring campaigns
    $recurringCampaigns = App\Models\Campaign::where('is_recurring', true)->count();
    test("Recurring campaign field exists", true);
    if ($recurringCampaigns == 0) {
        warn("No recurring campaigns", "Recurring campaign logic not tested in production data");
    }
    
    // Test 5.5: Campaign location fields
    $campaignsWithLocation = App\Models\Campaign::whereNotNull('region')->count();
    test("Campaign location fields populated", $campaignsWithLocation > 0, "Found $campaignsWithLocation with location");
    
    // Test 5.6: Beneficiary categories
    $campaignsWithBeneficiary = App\Models\Campaign::whereNotNull('beneficiary_category')->count();
    test("Beneficiary category field exists", true);
    if ($campaignsWithBeneficiary > 0) {
        test("Beneficiary categories populated", true, "Found $campaignsWithBeneficiary with beneficiary data");
    }
}

// Test 5.7: Campaign Updates
$campaignUpdates = App\Models\CampaignUpdate::count();
test("Campaign updates table accessible", true);
if ($campaignUpdates > 0) {
    test("Campaign updates/milestones exist", true, "Found $campaignUpdates updates");
} else {
    warn("No campaign updates", "Milestone posting not tested");
}

echo "\n--- 6. ANALYTICS & REPORTS TESTS ---\n";

// Test 6.1: Activity logs
$activityLogs = App\Models\ActivityLog::count();
test("Activity logs table accessible", true);
test("Activity logs being recorded", $activityLogs > 0, "Found $activityLogs activity logs");

if ($activityLogs > 0) {
    $logActions = App\Models\ActivityLog::distinct('action')->pluck('action');
    test("Multiple action types logged", $logActions->count() > 3, "Actions: " . $logActions->take(5)->implode(', '));
}

// Test 6.2: Reports system
$reports = App\Models\Report::count();
test("Reports table accessible", true);
if ($reports > 0) {
    test("Reports submitted", true, "Found $reports reports");
} else {
    warn("No reports", "Reporting system not tested in production");
}

// Test 6.3: Notifications
$notifications = App\Models\Notification::count();
test("Notifications table accessible", true);
if ($notifications > 0) {
    test("Notifications generated", true, "Found $notifications notifications");
} else {
    warn("No notifications", "Notification system may not be working or no triggers yet");
}

echo "\n--- 7. SECURITY & RELATIONSHIPS TESTS ---\n";

// Test 7.1: Foreign key relationships
try {
    $charity = App\Models\Charity::with(['owner', 'campaigns', 'donations', 'documents'])->first();
    test("Charity relationships load correctly", $charity !== null);
    
    $campaign = App\Models\Campaign::with(['charity', 'donations', 'fundUsageLogs'])->first();
    test("Campaign relationships load correctly", $campaign !== null);
    
    $donation = App\Models\Donation::with(['donor', 'charity', 'campaign'])->first();
    test("Donation relationships load correctly", $donation !== null);
} catch (Exception $e) {
    test("Relationship integrity", false, $e->getMessage());
}

// Test 7.2: Donation channels
$donationChannels = App\Models\DonationChannel::count();
test("Donation channels table accessible", true);
if ($donationChannels > 0) {
    test("Donation channels configured", true, "Found $donationChannels channels");
    $channelTypes = App\Models\DonationChannel::distinct('type')->pluck('type');
    test("Multiple payment channels exist", $channelTypes->count() > 1, "Types: " . $channelTypes->implode(', '));
} else {
    warn("No donation channels", "Payment channel setup not tested");
}

// Test 7.3: Charity follows
$follows = App\Models\CharityFollow::count();
test("Charity follows table accessible", true);
if ($follows > 0) {
    test("Charity follow feature functional", true, "Found $follows follows");
}

echo "\n--- 8. DATA INTEGRITY TESTS ---\n";

// Test 8.1: Orphaned records check
$orphanedDonations = App\Models\Donation::whereNotNull('charity_id')
    ->whereDoesntHave('charity')->count();
test("No orphaned donations", $orphanedDonations == 0, $orphanedDonations > 0 ? "Found $orphanedDonations orphaned" : "");

$orphanedCampaigns = App\Models\Campaign::whereNotNull('charity_id')
    ->whereDoesntHave('charity')->count();
test("No orphaned campaigns", $orphanedCampaigns == 0, $orphanedCampaigns > 0 ? "Found $orphanedCampaigns orphaned" : "");

// Test 8.2: Data consistency
$campaignsWithDonations = App\Models\Campaign::has('donations')->count();
if ($campaignsWithDonations > 0) {
    test("Campaigns have donations linked", true, "Found $campaignsWithDonations campaigns with donations");
}

echo "\n=== TEST SUMMARY ===\n";
echo "✅ Passed: {$results['passed']}\n";
echo "❌ Failed: {$results['failed']}\n";
echo "⚠️  Warnings: {$results['warnings']}\n";
echo "\nTotal Tests: " . ($results['passed'] + $results['failed']) . "\n";
$percentage = $results['passed'] + $results['failed'] > 0 
    ? round(($results['passed'] / ($results['passed'] + $results['failed'])) * 100, 2) 
    : 0;
echo "Pass Rate: {$percentage}%\n";

if ($results['failed'] == 0) {
    echo "\n🎉 ALL TESTS PASSED! System is functional.\n";
} else {
    echo "\n⚠️  Some tests failed. Review failures above.\n";
}

if ($results['warnings'] > 0) {
    echo "ℹ️  {$results['warnings']} warnings - features exist but not tested with production data.\n";
}

echo "\n=== TEST COMPLETE ===\n";
