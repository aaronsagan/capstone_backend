<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DATABASE CONNECTION TEST ===\n";
echo "Database: " . DB::connection()->getDatabaseName() . "\n\n";

echo "=== DATA COUNTS ===\n";
echo "Users: " . App\Models\User::count() . "\n";
echo "Charities: " . App\Models\Charity::count() . "\n";
echo "Campaigns: " . App\Models\Campaign::count() . "\n";
echo "Donations: " . App\Models\Donation::count() . "\n";
echo "Fund Usage Logs: " . App\Models\FundUsageLog::count() . "\n";
echo "Campaign Updates: " . App\Models\CampaignUpdate::count() . "\n";
echo "Notifications: " . App\Models\Notification::count() . "\n";
echo "Reports: " . App\Models\Report::count() . "\n";
echo "Activity Logs: " . App\Models\ActivityLog::count() . "\n\n";

echo "=== USER ROLES ===\n";
$roles = App\Models\User::select('role', DB::raw('count(*) as count'))
    ->groupBy('role')
    ->get();
foreach ($roles as $role) {
    echo ucfirst($role->role) . ": " . $role->count . "\n";
}
echo "\n";

echo "=== CHARITY VERIFICATION STATUS ===\n";
$statuses = App\Models\Charity::select('verification_status', DB::raw('count(*) as count'))
    ->groupBy('verification_status')
    ->get();
foreach ($statuses as $status) {
    echo ucfirst($status->verification_status) . ": " . $status->count . "\n";
}
echo "\n";

echo "=== CAMPAIGN STATUS ===\n";
$campaignStatuses = App\Models\Campaign::select('status', DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();
foreach ($campaignStatuses as $status) {
    echo ucfirst($status->status) . ": " . $status->count . "\n";
}
echo "\n";

echo "=== DONATION STATUS ===\n";
$donationStatuses = App\Models\Donation::select('status', DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();
foreach ($donationStatuses as $status) {
    echo ucfirst($status->status) . ": " . $status->count . "\n";
}
echo "\n";

echo "=== RECURRING DONATIONS ===\n";
$recurringDonations = App\Models\Donation::where('is_recurring', true)->count();
echo "Total Recurring Donations: " . $recurringDonations . "\n\n";

echo "=== RECURRING CAMPAIGNS ===\n";
$recurringCampaigns = App\Models\Campaign::where('is_recurring', true)->count();
echo "Total Recurring Campaigns: " . $recurringCampaigns . "\n\n";

echo "=== TEST COMPLETE ===\n";
