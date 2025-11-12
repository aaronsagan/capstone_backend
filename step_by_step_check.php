<?php

/**
 * Step-by-Step System Check
 * 
 * Tests each part of the refund system one by one
 * Shows exactly what's working and what's not
 * 
 * Run: php step_by_step_check.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function pass($msg) {
    echo "✅ PASS: {$msg}\n";
}

function fail($msg) {
    echo "❌ FAIL: {$msg}\n";
}

function info($msg) {
    echo "ℹ️  INFO: {$msg}\n";
}

echo "\n";
echo "==============================================\n";
echo "   STEP-BY-STEP REFUND SYSTEM CHECK\n";
echo "==============================================\n\n";

// TEST 1: Database Connection
echo "TEST 1: Database Connection\n";
echo "-------------------------------------------\n";
try {
    DB::connection()->getPdo();
    pass("Database connection successful");
    $dbName = DB::connection()->getDatabaseName();
    info("Connected to database: {$dbName}");
} catch (\Exception $e) {
    fail("Database connection failed: {$e->getMessage()}");
    exit(1);
}
echo "\n";

// TEST 2: donations table exists
echo "TEST 2: Donations Table\n";
echo "-------------------------------------------\n";
try {
    $exists = DB::select("SHOW TABLES LIKE 'donations'");
    if (!empty($exists)) {
        pass("Donations table exists");
    } else {
        fail("Donations table not found");
        exit(1);
    }
} catch (\Exception $e) {
    fail("Error checking donations table: {$e->getMessage()}");
    exit(1);
}
echo "\n";

// TEST 3: status column and ENUM
echo "TEST 3: Status Column (ENUM with 'refunded')\n";
echo "-------------------------------------------\n";
try {
    $column = DB::select("SHOW COLUMNS FROM donations WHERE Field = 'status'")[0] ?? null;
    if ($column) {
        pass("Status column exists");
        info("Type: {$column->Type}");
        
        if (stripos($column->Type, 'refunded') !== false) {
            pass("'refunded' status IS in the ENUM ✅");
        } else {
            fail("'refunded' status NOT in ENUM - Need to run migration!");
            info("Current ENUM values: {$column->Type}");
            info("FIX: Run 'php artisan migrate'");
        }
    } else {
        fail("Status column not found");
    }
} catch (\Exception $e) {
    fail("Error: {$e->getMessage()}");
}
echo "\n";

// TEST 4: is_refunded column
echo "TEST 4: is_refunded Column\n";
echo "-------------------------------------------\n";
try {
    $column = DB::select("SHOW COLUMNS FROM donations WHERE Field = 'is_refunded'")[0] ?? null;
    if ($column) {
        pass("is_refunded column exists");
        info("Type: {$column->Type}, Default: {$column->Default}");
    } else {
        fail("is_refunded column NOT found - Need to run migration!");
        info("FIX: Run 'php artisan migrate'");
    }
} catch (\Exception $e) {
    fail("Error: {$e->getMessage()}");
}
echo "\n";

// TEST 5: refunded_at column
echo "TEST 5: refunded_at Column\n";
echo "-------------------------------------------\n";
try {
    $column = DB::select("SHOW COLUMNS FROM donations WHERE Field = 'refunded_at'")[0] ?? null;
    if ($column) {
        pass("refunded_at column exists");
        info("Type: {$column->Type}");
    } else {
        fail("refunded_at column NOT found - Need to run migration!");
        info("FIX: Run 'php artisan migrate'");
    }
} catch (\Exception $e) {
    fail("Error: {$e->getMessage()}");
}
echo "\n";

// TEST 6: refund_requests table
echo "TEST 6: Refund Requests Table\n";
echo "-------------------------------------------\n";
try {
    $exists = DB::select("SHOW TABLES LIKE 'refund_requests'");
    if (!empty($exists)) {
        pass("refund_requests table exists");
        
        $count = DB::table('refund_requests')->count();
        info("Total refund requests: {$count}");
        
        $approved = DB::table('refund_requests')->where('status', 'approved')->count();
        info("Approved refunds: {$approved}");
    } else {
        fail("refund_requests table not found");
    }
} catch (\Exception $e) {
    fail("Error: {$e->getMessage()}");
}
echo "\n";

// TEST 7: Find specific refund (₱2,070)
echo "TEST 7: Finding Aeron's Refund (₱2,070)\n";
echo "-------------------------------------------\n";
try {
    $refund = DB::table('refund_requests as rr')
        ->join('donations as d', 'rr.donation_id', '=', 'd.id')
        ->join('users as u', 'rr.user_id', '=', 'u.id')
        ->where('d.amount', 2070.00)
        ->select(
            'rr.id as refund_id',
            'rr.status as refund_status',
            'd.id as donation_id',
            'd.status as donation_status',
            'd.is_refunded',
            'd.amount',
            'u.name as donor_name'
        )
        ->first();
    
    if ($refund) {
        pass("Found refund in database");
        info("Refund ID: {$refund->refund_id}");
        info("Donor: {$refund->donor_name}");
        info("Amount: ₱" . number_format($refund->amount, 2));
        info("Refund Status: {$refund->refund_status}");
        info("Donation Status: {$refund->donation_status}");
        info("Is Refunded Flag: " . ($refund->is_refunded ? 'true' : 'false'));
        
        echo "\n";
        
        if ($refund->refund_status === 'approved') {
            if ($refund->donation_status === 'refunded' && $refund->is_refunded) {
                pass("✅ REFUND IS WORKING CORRECTLY!");
                info("Donation is properly marked as refunded");
            } else {
                fail("❌ REFUND NOT WORKING!");
                info("Refund is approved but donation status is wrong");
                info("Expected: status='refunded', is_refunded=true");
                info("Actual: status='{$refund->donation_status}', is_refunded=" . ($refund->is_refunded ? 'true' : 'false'));
            }
        } else {
            info("Refund status is '{$refund->refund_status}' (not approved yet)");
        }
    } else {
        info("No refund with amount ₱2,070 found");
        info("Looking for any approved refunds...");
        
        $anyRefund = DB::table('refund_requests')->where('status', 'approved')->first();
        if ($anyRefund) {
            info("Found approved refund ID: {$anyRefund->id}");
        } else {
            info("No approved refunds in database");
        }
    }
} catch (\Exception $e) {
    fail("Error: {$e->getMessage()}");
}
echo "\n";

// TEST 8: Check campaign totals calculation
echo "TEST 8: Campaign Totals Calculation\n";
echo "-------------------------------------------\n";
try {
    $campaign = DB::table('campaigns')
        ->where('title', 'like', '%IFL%')
        ->orWhere('title', 'like', '%Integrated%Foundational%')
        ->first();
    
    if ($campaign) {
        pass("Found IFL campaign");
        info("Campaign ID: {$campaign->id}");
        info("Campaign: {$campaign->title}");
        info("Stored Total: ₱" . number_format($campaign->total_donations_received, 2));
        
        // Calculate what it should be
        $calculated = DB::table('donations')
            ->where('campaign_id', $campaign->id)
            ->where('status', 'completed')
            ->where('is_refunded', 0)
            ->sum('amount');
        
        info("Calculated Total (excluding refunds): ₱" . number_format($calculated, 2));
        
        $diff = abs($campaign->total_donations_received - $calculated);
        if ($diff < 0.01) {
            pass("✅ Campaign total is CORRECT!");
            info("Refunds are properly excluded from totals");
        } else {
            fail("❌ Campaign total is WRONG!");
            info("Difference: ₱" . number_format($diff, 2));
            info("Total needs recalculation");
        }
    } else {
        info("IFL campaign not found");
    }
} catch (\Exception $e) {
    fail("Error: {$e->getMessage()}");
}
echo "\n";

// TEST 9: Check if update is possible
echo "TEST 9: Test Database Update Permission\n";
echo "-------------------------------------------\n";
try {
    // Try a harmless update
    $testUpdate = DB::table('donations')
        ->where('id', 1)
        ->update(['updated_at' => now()]);
    
    pass("Database UPDATE permission works");
    info("Can modify donations table");
} catch (\Exception $e) {
    fail("Cannot update donations table: {$e->getMessage()}");
    info("Database permission issue or record locked");
}
echo "\n";

// TEST 10: CharityRefundController
echo "TEST 10: CharityRefundController File\n";
echo "-------------------------------------------\n";
$controllerPath = __DIR__ . '/app/Http/Controllers/CharityRefundController.php';
if (file_exists($controllerPath)) {
    pass("CharityRefundController.php exists");
    
    $content = file_get_contents($controllerPath);
    
    $hasRefundedStatus = strpos($content, "'status' => 'refunded'") !== false || 
                         strpos($content, '"status" => "refunded"') !== false;
    
    $hasIsRefunded = strpos($content, "'is_refunded' => true") !== false ||
                     strpos($content, '"is_refunded" => true') !== false;
    
    if ($hasRefundedStatus) {
        pass("Controller sets status='refunded'");
    } else {
        fail("Controller does NOT set status='refunded'");
        info("Controller needs to be updated");
    }
    
    if ($hasIsRefunded) {
        pass("Controller sets is_refunded=true");
    } else {
        fail("Controller does NOT set is_refunded=true");
        info("Controller needs to be updated");
    }
} else {
    fail("CharityRefundController.php NOT found!");
}
echo "\n";

// FINAL SUMMARY
echo "==============================================\n";
echo "SUMMARY\n";
echo "==============================================\n\n";

$allGood = true;

// Check critical items
$criticalChecks = [
    'status_enum' => false,
    'is_refunded_column' => false,
    'refund_exists' => false,
    'refund_correct' => false
];

try {
    $statusCol = DB::select("SHOW COLUMNS FROM donations WHERE Field = 'status'")[0] ?? null;
    $criticalChecks['status_enum'] = $statusCol && stripos($statusCol->Type, 'refunded') !== false;
    
    $isRefundedCol = DB::select("SHOW COLUMNS FROM donations WHERE Field = 'is_refunded'")[0] ?? null;
    $criticalChecks['is_refunded_column'] = !empty($isRefundedCol);
    
    $refund = DB::table('refund_requests as rr')
        ->join('donations as d', 'rr.donation_id', '=', 'd.id')
        ->where('d.amount', 2070.00)
        ->select('rr.status as refund_status', 'd.status as donation_status', 'd.is_refunded')
        ->first();
    
    $criticalChecks['refund_exists'] = !empty($refund);
    
    if ($refund && $refund->refund_status === 'approved') {
        $criticalChecks['refund_correct'] = ($refund->donation_status === 'refunded' && $refund->is_refunded);
    }
} catch (\Exception $e) {
    // errors already reported above
}

// Display results
if ($criticalChecks['status_enum'] && $criticalChecks['is_refunded_column']) {
    echo "✅ DATABASE SCHEMA: Ready\n";
} else {
    echo "❌ DATABASE SCHEMA: Needs migration\n";
    echo "   FIX: Run 'php artisan migrate'\n";
    $allGood = false;
}

if ($criticalChecks['refund_exists']) {
    echo "✅ REFUND DATA: Found in database\n";
    
    if ($criticalChecks['refund_correct']) {
        echo "✅ REFUND STATUS: WORKING CORRECTLY! 🎉\n";
        echo "\n";
        echo "🎉 THE REFUND SYSTEM IS WORKING!\n";
        echo "   - Donation status is 'refunded'\n";
        echo "   - is_refunded flag is true\n";
        echo "   - Everything is correct!\n";
    } else {
        echo "❌ REFUND STATUS: Needs fixing\n";
        echo "   FIX: Run 'php manual_force_update.php'\n";
        $allGood = false;
    }
} else {
    echo "ℹ️  REFUND DATA: No ₱2,070 refund found\n";
}

echo "\n";

if (!$allGood) {
    echo "RECOMMENDED ACTION:\n";
    echo "Run: cd .. && .\\diagnose-and-fix.ps1\n";
} else {
    echo "✅ ALL SYSTEMS OPERATIONAL!\n";
    echo "If browser still shows 'Completed', hard refresh (Ctrl+F5)\n";
}

echo "\n";
