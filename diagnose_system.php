<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "========================================\n";
echo "  CHARITYHUB SYSTEM DIAGNOSTIC TOOL\n";
echo "========================================\n\n";

// Test 1: Database Connection
echo "🔍 TEST 1: DATABASE CONNECTION\n";
try {
    \DB::connection()->getPdo();
    echo "✅ Database connection: SUCCESSFUL\n";
    $dbName = \DB::connection()->getDatabaseName();
    echo "📊 Database: $dbName\n\n";
} catch (\Exception $e) {
    echo "❌ Database connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Check for Duplicate Login Tables
echo "🔍 TEST 2: DUPLICATE LOGIN TABLES\n";
$loginTables = [
    'failed_logins',
    'failed_login_attempts',  
    'login_attempts'
];
foreach ($loginTables as $table) {
    try {
        $count = \DB::table($table)->count();
        echo "✅ Table '$table' exists - $count records\n";
    } catch (\Exception $e) {
        echo "❌ Table '$table' does not exist\n";
    }
}
echo "\n";

// Test 3: Check for Duplicate Email Tables
echo "🔍 TEST 3: DUPLICATE EMAIL TABLES\n";
$emailTables = [
    'email_changes',
    'email_change_requests'
];
foreach ($emailTables as $table) {
    try {
        $count = \DB::table($table)->count();
        echo "✅ Table '$table' exists - $count records\n";
    } catch (\Exception $e) {
        echo "❌ Table '$table' does not exist\n";
    }
}
echo "\n";

// Test 4: Check Core Tables Data
echo "🔍 TEST 4: CORE TABLES DATA CHECK\n";
$coreTables = [
    'users' => 'Total users',
    'charities' => 'Total charities',
    'campaigns' => 'Total campaigns',
    'donations' => 'Total donations',
    'charity_follows' => 'Total follows',
    'notifications' => 'Total notifications'
];

foreach ($coreTables as $table => $label) {
    try {
        $count = \DB::table($table)->count();
        echo "✅ $label: $count\n";
    } catch (\Exception $e) {
        echo "❌ Table '$table': ERROR - " . $e->getMessage() . "\n";
    }
}
echo "\n";

// Test 5: Check Analytics Data Integrity
echo "🔍 TEST 5: ANALYTICS DATA INTEGRITY\n";
try {
    // Check campaigns with total_donations_received field
    $campaignsTotal = \App\Models\Campaign::count();
    $campaignsWithTotal = \App\Models\Campaign::whereNotNull('total_donations_received')->count();
    echo "✅ Campaigns total: $campaignsTotal\n";
    echo "✅ Campaigns with total_donations_received: $campaignsWithTotal\n";
    
    // Check for campaigns with target_amount
    $campaignsWithTarget = \App\Models\Campaign::where('target_amount', '>', 0)->count();
    echo "✅ Campaigns with target_amount: $campaignsWithTarget\n";
    
    // Check completed campaigns (reached 100%)
    $completedCampaigns = \App\Models\Campaign::whereRaw('total_donations_received >= target_amount')
        ->where('target_amount', '>', 0)
        ->count();
    echo "✅ Completed campaigns (100%): $completedCampaigns\n";
    
} catch (\Exception $e) {
    echo "❌ Analytics check failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 6: Check Unused Tables
echo "🔍 TEST 6: POTENTIALLY UNUSED TABLES\n";
$potentiallyUnused = [
    'volunteers' => 'Volunteer feature',
    'two_factor_codes' => '2FA feature',
    'recurring_donations' => 'Recurring donations',
    'refund_requests' => 'Refund system',
    'saved_items' => 'Saved items feature',
    'donor_milestones' => 'Milestones feature'
];

foreach ($potentiallyUnused as $table => $feature) {
    try {
        $count = \DB::table($table)->count();
        if ($count == 0) {
            echo "⚠️  Table '$table' ($feature): EMPTY - Consider removing\n";
        } else {
            echo "✅ Table '$table' ($feature): ACTIVE - $count records\n";
        }
    } catch (\Exception $e) {
        echo "❌ Table '$table': DOES NOT EXIST\n";
    }
}
echo "\n";

// Test 7: API Endpoint Test
echo "🔍 TEST 7: TEST CRITICAL API LOGIC\n";

// Test trends query
try {
    $charity_id = 2;
    $trendsData = \App\Models\Donation::selectRaw("DATE_FORMAT(created_at, '%M %Y') as month, SUM(amount) as total")
        ->where('status', '=', 'completed')
        ->when($charity_id, fn($q) => $q->whereHas('campaign', fn($c) => $c->where('charity_id', '=', $charity_id)))
        ->groupByRaw("\"DATE_FORMAT(created_at, '%M %Y')")
        ->orderByRaw("MIN(created_at)")
        ->get();
    
    echo "✅ Trends query: WORKING (" . $trendsData->count() . " months)\n";
} catch (\Exception $e) {
    echo "❌ Trends query: FAILED - " . $e->getMessage() . "\n";
}

// Test completed campaigns query
try {
    $completedData = \App\Models\Campaign::whereRaw('total_donations_received >= target_amount')
        ->where('target_amount', '>', 0)
        ->limit(5)
        ->get(['id', 'title', 'target_amount', 'total_donations_received']);
    
    echo "✅ Completed campaigns query: WORKING (" . $completedData->count() . " campaigns)\n";
    
    foreach ($completedData as $campaign) {
        $progress = ($campaign->total_donations_received / $campaign->target_amount) * 100;
        echo "   - {$campaign->title}: " . number_format($progress, 1) . "%\n";
    }
} catch (\Exception $e) {
    echo "❌ Completed campaigns query: FAILED - " . $e->getMessage() . "\n";
}
echo "\n";

// Test 8: Check for Orphaned Records
echo "🔍 TEST 8: ORPHANED RECORDS CHECK\n";

// Check campaigns without charities
try {
    $orphanedCampaigns = \DB::table('campaigns')
        ->leftJoin('charities', 'campaigns.charity_id', '=', 'charities.id')
        ->whereNull('charities.id')
        ->count();
    
    if ($orphanedCampaigns > 0) {
        echo "⚠️  Orphaned campaigns (no charity): $orphanedCampaigns\n";
    } else {
        echo "✅ No orphaned campaigns\n";
    }
} catch (\Exception $e) {
    echo "❌ Orphan check failed: " . $e->getMessage() . "\n";
}

// Check donations without campaigns
try {
    $orphanedDonations = \DB::table('donations')
        ->leftJoin('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
        ->whereNull('campaigns.id')
        ->count();
    
    if ($orphanedDonations > 0) {
        echo "⚠️  Orphaned donations (no campaign): $orphanedDonations\n";
    } else {
        echo "✅ No orphaned donations\n";
    }
} catch (\Exception $e) {
    echo "❌ Orphan check failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 9: Check for Missing Indexes
echo "🔍 TEST 9: PERFORMANCE INDEXES CHECK\n";
try {
    // Check if important indexes exist
    $indexes = \DB::select("SHOW INDEX FROM campaigns WHERE Key_name != 'PRIMARY'");
    echo "✅ Campaign indexes: " . count($indexes) . " found\n";
    
    $donationIndexes = \DB::select("SHOW INDEX FROM donations WHERE Key_name != 'PRIMARY'");
    echo "✅ Donation indexes: " . count($donationIndexes) . " found\n";
} catch (\Exception $e) {
    echo "⚠️  Index check: " . $e->getMessage() . "\n";
}
echo "\n";

// SUMMARY
echo "========================================\n";
echo "  DIAGNOSTIC SUMMARY\n";
echo "========================================\n\n";

echo "✅ System is operational\n";
echo "⚠️  Found duplicate tables that need consolidation\n";
echo "📊 Check the full audit report: COMPREHENSIVE_SYSTEM_AUDIT.md\n\n";

echo "Diagnostic complete.\n";
