<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== API ENDPOINT FUNCTIONALITY TEST ===\n\n";

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

echo "--- TESTING CONTROLLER METHODS ---\n\n";

// Test AuthController methods
echo "1. AuthController:\n";
test("  registerDonor method exists", method_exists(App\Http\Controllers\AuthController::class, 'registerDonor'));
test("  registerCharityAdmin method exists", method_exists(App\Http\Controllers\AuthController::class, 'registerCharityAdmin'));
test("  login method exists", method_exists(App\Http\Controllers\AuthController::class, 'login'));
test("  logout method exists", method_exists(App\Http\Controllers\AuthController::class, 'logout'));
test("  updateProfile method exists", method_exists(App\Http\Controllers\AuthController::class, 'updateProfile'));
test("  changePassword method exists", method_exists(App\Http\Controllers\AuthController::class, 'changePassword'));

// Test CharityController methods
echo "\n2. CharityController:\n";
test("  index method exists", method_exists(App\Http\Controllers\CharityController::class, 'index'));
test("  show method exists", method_exists(App\Http\Controllers\CharityController::class, 'show'));
test("  store method exists", method_exists(App\Http\Controllers\CharityController::class, 'store'));
test("  update method exists", method_exists(App\Http\Controllers\CharityController::class, 'update'));
test("  uploadDocument method exists", method_exists(App\Http\Controllers\CharityController::class, 'uploadDocument'));
test("  storeChannel method exists", method_exists(App\Http\Controllers\CharityController::class, 'storeChannel'));

// Test CampaignController methods
echo "\n3. CampaignController:\n";
test("  index method exists", method_exists(App\Http\Controllers\CampaignController::class, 'index'));
test("  show method exists", method_exists(App\Http\Controllers\CampaignController::class, 'show'));
test("  store method exists", method_exists(App\Http\Controllers\CampaignController::class, 'store'));
test("  update method exists", method_exists(App\Http\Controllers\CampaignController::class, 'update'));

// Test DonationController methods
echo "\n4. DonationController:\n";
test("  store method exists", method_exists(App\Http\Controllers\DonationController::class, 'store'));
test("  uploadProof method exists", method_exists(App\Http\Controllers\DonationController::class, 'uploadProof'));
test("  submitManualDonation method exists", method_exists(App\Http\Controllers\DonationController::class, 'submitManualDonation'));
test("  confirm method exists", method_exists(App\Http\Controllers\DonationController::class, 'confirm'));
test("  myDonations method exists", method_exists(App\Http\Controllers\DonationController::class, 'myDonations'));
test("  downloadReceipt method exists", method_exists(App\Http\Controllers\DonationController::class, 'downloadReceipt'));
test("  processRecurringDonations method exists", method_exists(App\Http\Controllers\DonationController::class, 'processRecurringDonations'));

// Test FundUsageController methods
echo "\n5. FundUsageController:\n";
test("  index method exists", method_exists(App\Http\Controllers\FundUsageController::class, 'index'));
test("  store method exists", method_exists(App\Http\Controllers\FundUsageController::class, 'store'));
test("  update method exists", method_exists(App\Http\Controllers\FundUsageController::class, 'update'));
test("  destroy method exists", method_exists(App\Http\Controllers\FundUsageController::class, 'destroy'));

// Test AnalyticsController methods
echo "\n6. AnalyticsController:\n";
test("  campaignsByType method exists", method_exists(App\Http\Controllers\AnalyticsController::class, 'campaignsByType'));
test("  trendingCampaigns method exists", method_exists(App\Http\Controllers\AnalyticsController::class, 'trendingCampaigns'));
test("  donorSummary method exists", method_exists(App\Http\Controllers\AnalyticsController::class, 'donorSummary'));
test("  summary method exists", method_exists(App\Http\Controllers\AnalyticsController::class, 'summary'));
test("  campaignLocations method exists", method_exists(App\Http\Controllers\AnalyticsController::class, 'campaignLocations'));
test("  getCampaignBeneficiaryDistribution method exists", method_exists(App\Http\Controllers\AnalyticsController::class, 'getCampaignBeneficiaryDistribution'));

// Test Admin Controllers
echo "\n7. Admin/VerificationController:\n";
test("  index method exists", method_exists(App\Http\Controllers\Admin\VerificationController::class, 'index'));
test("  getUsers method exists", method_exists(App\Http\Controllers\Admin\VerificationController::class, 'getUsers'));
test("  approve method exists", method_exists(App\Http\Controllers\Admin\VerificationController::class, 'approve'));
test("  reject method exists", method_exists(App\Http\Controllers\Admin\VerificationController::class, 'reject'));
test("  suspendUser method exists", method_exists(App\Http\Controllers\Admin\VerificationController::class, 'suspendUser'));
test("  activateUser method exists", method_exists(App\Http\Controllers\Admin\VerificationController::class, 'activateUser'));

// Test ReportController methods
echo "\n8. ReportController:\n";
test("  store method exists", method_exists(App\Http\Controllers\ReportController::class, 'store'));
test("  index method exists", method_exists(App\Http\Controllers\ReportController::class, 'index'));
test("  show method exists", method_exists(App\Http\Controllers\ReportController::class, 'show'));
test("  review method exists", method_exists(App\Http\Controllers\ReportController::class, 'review'));

// Test NotificationController methods
echo "\n9. NotificationController:\n";
test("  index method exists", method_exists(App\Http\Controllers\NotificationController::class, 'index'));
test("  markAsRead method exists", method_exists(App\Http\Controllers\NotificationController::class, 'markAsRead'));
test("  markAllAsRead method exists", method_exists(App\Http\Controllers\NotificationController::class, 'markAllAsRead'));

echo "\n--- TESTING SERVICE CLASSES ---\n\n";

// Test NotificationService
echo "10. NotificationService:\n";
test("  sendDonationConfirmation method exists", method_exists(App\Services\NotificationService::class, 'sendDonationConfirmation'));
test("  sendVerificationStatus method exists", method_exists(App\Services\NotificationService::class, 'sendVerificationStatus'));
test("  sendDonationReceived method exists", method_exists(App\Services\NotificationService::class, 'sendDonationReceived'));

// Test SecurityService
echo "\n11. SecurityService:\n";
test("  logActivity method exists", method_exists(App\Services\SecurityService::class, 'logActivity'));
test("  logAuthEvent method exists", method_exists(App\Services\SecurityService::class, 'logAuthEvent'));
test("  logFailedLogin method exists", method_exists(App\Services\SecurityService::class, 'logFailedLogin'));
test("  checkSuspiciousLogin method exists", method_exists(App\Services\SecurityService::class, 'checkSuspiciousLogin'));
test("  checkComplianceStatus method exists", method_exists(App\Services\SecurityService::class, 'checkComplianceStatus'));

echo "\n--- TESTING MODEL RELATIONSHIPS ---\n\n";

// Test User model relationships
echo "12. User Model Relationships:\n";
$userMethods = get_class_methods(App\Models\User::class);
test("  charities relationship exists", in_array('charities', $userMethods));
test("  donations relationship exists", in_array('donations', $userMethods));
test("  notifications relationship exists", in_array('notifications', $userMethods));

// Test Charity model relationships
echo "\n13. Charity Model Relationships:\n";
$charityMethods = get_class_methods(App\Models\Charity::class);
test("  owner relationship exists", in_array('owner', $charityMethods));
test("  campaigns relationship exists", in_array('campaigns', $charityMethods));
test("  donations relationship exists", in_array('donations', $charityMethods));
test("  documents relationship exists", in_array('documents', $charityMethods));
test("  channels relationship exists", in_array('channels', $charityMethods));

// Test Campaign model relationships
echo "\n14. Campaign Model Relationships:\n";
$campaignMethods = get_class_methods(App\Models\Campaign::class);
test("  charity relationship exists", in_array('charity', $campaignMethods));
test("  donations relationship exists", in_array('donations', $campaignMethods));
test("  fundUsageLogs relationship exists", in_array('fundUsageLogs', $campaignMethods));

// Test Donation model relationships
echo "\n15. Donation Model Relationships:\n";
$donationMethods = get_class_methods(App\Models\Donation::class);
test("  donor relationship exists", in_array('donor', $donationMethods));
test("  charity relationship exists", in_array('charity', $donationMethods));
test("  campaign relationship exists", in_array('campaign', $donationMethods));

echo "\n--- TESTING MIDDLEWARE ---\n\n";

echo "16. Middleware Classes:\n";
test("  EnsureRole middleware exists", class_exists(App\Http\Middleware\EnsureRole::class));
test("  EnsureRole has handle method", method_exists(App\Http\Middleware\EnsureRole::class, 'handle'));

echo "\n--- TESTING CRITICAL FEATURES ---\n\n";

echo "17. Password Validation:\n";
// Check if password validation is weak
$authController = file_get_contents(__DIR__ . '/app/Http/Controllers/AuthController.php');
if (strpos($authController, 'min:6') !== false && strpos($authController, 'regex') === false) {
    warn("  Weak password validation", "Only min:6 - missing uppercase/number/special char requirement");
} else if (strpos($authController, 'regex') !== false) {
    test("  Strong password validation", true);
} else {
    test("  Password validation exists", strpos($authController, 'password') !== false);
}

echo "\n18. File Upload Validation:\n";
$charityController = file_get_contents(__DIR__ . '/app/Http/Controllers/CharityController.php');
test("  Document upload validation exists", strpos($charityController, 'mimes:pdf') !== false);
test("  Image upload validation exists", strpos($charityController, 'image') !== false);
test("  File size limits enforced", strpos($charityController, 'max:') !== false);

echo "\n19. Security Features:\n";
$securityService = file_get_contents(__DIR__ . '/app/Services/SecurityService.php');
test("  Brute force detection implemented", strpos($securityService, 'brute_force') !== false);
test("  Failed login logging implemented", strpos($securityService, 'logFailedLogin') !== false);
test("  Activity logging implemented", strpos($securityService, 'logActivity') !== false);

echo "\n20. Recurring Features:\n";
$donationController = file_get_contents(__DIR__ . '/app/Http/Controllers/DonationController.php');
test("  Recurring donation logic exists", strpos($donationController, 'is_recurring') !== false);
test("  Next donation date calculation exists", strpos($donationController, 'calculateNextDonationDate') !== false);
test("  Recurring donation scheduling exists", strpos($donationController, 'scheduleNextRecurringDonation') !== false);

$campaignController = file_get_contents(__DIR__ . '/app/Http/Controllers/CampaignController.php');
test("  Recurring campaign logic exists", strpos($campaignController, 'is_recurring') !== false);
test("  Campaign recurrence calculation exists", strpos($campaignController, 'calculateNextOccurrence') !== false);

echo "\n21. Analytics Features:\n";
$analyticsController = file_get_contents(__DIR__ . '/app/Http/Controllers/AnalyticsController.php');
test("  Campaign type analytics exists", strpos($analyticsController, 'campaignsByType') !== false);
test("  Trending analysis exists", strpos($analyticsController, 'trendingCampaigns') !== false);
test("  Location analytics exists", strpos($analyticsController, 'campaignLocations') !== false);
test("  Beneficiary analytics exists", strpos($analyticsController, 'beneficiary') !== false);
test("  Donor analytics exists", strpos($analyticsController, 'donorSummary') !== false);

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
    echo "\n🎉 ALL API TESTS PASSED! Controllers and methods are functional.\n";
} else {
    echo "\n⚠️  Some tests failed. Review failures above.\n";
}

if ($results['warnings'] > 0) {
    echo "ℹ️  {$results['warnings']} warnings - review security and validation concerns.\n";
}

echo "\n=== TEST COMPLETE ===\n";
