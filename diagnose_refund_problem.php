<?php

/**
 * Deep Diagnosis: Why Refund Status Won't Change
 * 
 * This script performs a comprehensive analysis to find exactly why
 * the refund approval isn't updating the donation status.
 * 
 * Run: php diagnose_refund_problem.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\{Donation, RefundRequest};

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "==============================================\n";
echo "   DEEP DIAGNOSIS: REFUND STATUS PROBLEM\n";
echo "==============================================\n\n";

$issues = [];
$checks = 0;

// Check 1: Database Schema - Status ENUM
echo "CHECK 1: Database Schema - Donations Status Column\n";
echo "-------------------------------------------\n";
$checks++;

try {
    $statusColumn = DB::select("SHOW COLUMNS FROM donations WHERE Field = 'status'")[0] ?? null;
    
    if ($statusColumn) {
        echo "✓ Status column exists\n";
        echo "  Type: {$statusColumn->Type}\n";
        echo "  Default: {$statusColumn->Default}\n";
        
        if (stripos($statusColumn->Type, 'refunded') !== false) {
            echo "  ✅ 'refunded' value IS in the ENUM\n";
        } else {
            echo "  ❌ 'refunded' value is NOT in the ENUM!\n";
            $issues[] = "CRITICAL: 'refunded' status missing from donations.status ENUM. Migration not run or failed.";
        }
        
        // Show all allowed values
        preg_match("/^enum\(\'(.*)\'\)$/", $statusColumn->Type, $matches);
        if (isset($matches[1])) {
            $values = explode("','", $matches[1]);
            echo "  Allowed values: " . implode(", ", $values) . "\n";
        }
    } else {
        echo "  ❌ Status column not found!\n";
        $issues[] = "CRITICAL: donations.status column doesn't exist!";
    }
} catch (\Exception $e) {
    echo "  ❌ Error checking status column: {$e->getMessage()}\n";
    $issues[] = "Error checking database schema: {$e->getMessage()}";
}

echo "\n";

// Check 2: is_refunded column
echo "CHECK 2: is_refunded Column\n";
echo "-------------------------------------------\n";
$checks++;

try {
    $isRefundedColumn = DB::select("SHOW COLUMNS FROM donations WHERE Field = 'is_refunded'")[0] ?? null;
    
    if ($isRefundedColumn) {
        echo "✓ is_refunded column exists\n";
        echo "  Type: {$isRefundedColumn->Type}\n";
        echo "  Default: {$isRefundedColumn->Default}\n";
    } else {
        echo "❌ is_refunded column NOT found!\n";
        $issues[] = "CRITICAL: is_refunded column missing. Run migration: 2025_11_08_000001_add_refund_fields_to_donations_table.php";
    }
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    $issues[] = "Error checking is_refunded column: {$e->getMessage()}";
}

echo "\n";

// Check 3: Find the specific approved refund
echo "CHECK 3: Locating Approved Refund (₱2,070)\n";
echo "-------------------------------------------\n";
$checks++;

try {
    $refundData = DB::table('refund_requests as rr')
        ->join('donations as d', 'rr.donation_id', '=', 'd.id')
        ->join('users as u', 'rr.user_id', '=', 'u.id')
        ->where('rr.status', 'approved')
        ->where('d.amount', 2070.00)
        ->select(
            'rr.id as refund_id',
            'rr.status as refund_status',
            'rr.reviewed_at',
            'd.id as donation_id',
            'd.status as donation_status',
            'd.is_refunded',
            'd.refunded_at',
            'd.amount',
            'u.name as donor_name',
            'u.email as donor_email'
        )
        ->first();
    
    if ($refundData) {
        echo "✓ Found approved refund:\n";
        echo "  Refund ID: {$refundData->refund_id}\n";
        echo "  Donation ID: {$refundData->donation_id}\n";
        echo "  Donor: {$refundData->donor_name}\n";
        echo "  Amount: ₱{$refundData->amount}\n";
        echo "  Current donation status: '{$refundData->donation_status}'\n";
        echo "  Current is_refunded: " . ($refundData->is_refunded ? 'true' : 'false') . "\n";
        
        if ($refundData->donation_status !== 'refunded') {
            echo "  ❌ Donation status should be 'refunded' but is '{$refundData->donation_status}'\n";
            $issues[] = "Donation #{$refundData->donation_id} status is '{$refundData->donation_status}' instead of 'refunded'";
        }
        
        if (!$refundData->is_refunded) {
            echo "  ❌ is_refunded flag should be true but is false\n";
            $issues[] = "Donation #{$refundData->donation_id} is_refunded flag is false";
        }
    } else {
        echo "❌ No approved refund with amount ₱2,070 found!\n";
        
        // Try to find ANY approved refunds
        $anyApproved = DB::table('refund_requests')->where('status', 'approved')->count();
        echo "  Found {$anyApproved} total approved refund(s)\n";
        
        if ($anyApproved === 0) {
            $issues[] = "No approved refunds found in database at all";
        } else {
            $issues[] = "Approved refund with ₱2,070 amount not found (but {$anyApproved} other approved refunds exist)";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    $issues[] = "Error locating refund: {$e->getMessage()}";
}

echo "\n";

// Check 4: Test direct database update
echo "CHECK 4: Test Direct Database Update Capability\n";
echo "-------------------------------------------\n";
$checks++;

try {
    if (isset($refundData) && $refundData) {
        echo "Testing if we can update donation #{$refundData->donation_id}...\n";
        
        // Try to update with raw SQL first
        $affected = DB::table('donations')
            ->where('id', $refundData->donation_id)
            ->update(['updated_at' => now()]);
        
        if ($affected > 0) {
            echo "✓ Can update donation via raw SQL\n";
            
            // Now try to update status
            try {
                $statusUpdate = DB::table('donations')
                    ->where('id', $refundData->donation_id)
                    ->update([
                        'status' => 'refunded',
                        'is_refunded' => true,
                        'refunded_at' => now()
                    ]);
                
                if ($statusUpdate > 0) {
                    echo "✓ Successfully updated status to 'refunded' via raw SQL!\n";
                    
                    // Verify it stuck
                    $verify = DB::table('donations')->where('id', $refundData->donation_id)->first();
                    echo "  Verified status: {$verify->status}\n";
                    echo "  Verified is_refunded: " . ($verify->is_refunded ? 'true' : 'false') . "\n";
                    
                    if ($verify->status === 'refunded') {
                        echo "  ✅ Update was successful and persisted!\n";
                        echo "  The problem is NOT with database permissions or constraints.\n";
                    } else {
                        echo "  ❌ Update didn't persist! Status reverted to: {$verify->status}\n";
                        $issues[] = "Database update succeeds but doesn't persist - possible trigger or constraint issue";
                    }
                } else {
                    echo "❌ Update returned 0 affected rows\n";
                    $issues[] = "SQL UPDATE command returns 0 affected rows - possible constraint or trigger blocking it";
                }
            } catch (\Exception $e) {
                echo "❌ Failed to update status: {$e->getMessage()}\n";
                $issues[] = "Cannot update status to 'refunded': {$e->getMessage()}";
            }
        } else {
            echo "❌ Cannot update donation at all!\n";
            $issues[] = "Database UPDATE permission issue or record locked";
        }
    } else {
        echo "⚠️  Skipping (no refund found to test)\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    $issues[] = "Error testing database update: {$e->getMessage()}";
}

echo "\n";

// Check 5: Eloquent Model Update Test
echo "CHECK 5: Eloquent Model Update Test\n";
echo "-------------------------------------------\n";
$checks++;

try {
    if (isset($refundData) && $refundData) {
        $donation = Donation::find($refundData->donation_id);
        
        if ($donation) {
            echo "✓ Found donation via Eloquent\n";
            echo "  Current status: {$donation->status}\n";
            echo "  Current is_refunded: " . ($donation->is_refunded ? 'true' : 'false') . "\n";
            
            // Check if fillable
            if (in_array('status', $donation->getFillable()) || in_array('is_refunded', $donation->getFillable())) {
                echo "✓ 'status' and 'is_refunded' are in fillable array\n";
            } else {
                echo "❌ 'status' or 'is_refunded' NOT in fillable array!\n";
                $issues[] = "Donation model doesn't have 'status' or 'is_refunded' in \$fillable property";
            }
            
            // Try Eloquent update
            try {
                $donation->status = 'refunded';
                $donation->is_refunded = true;
                $donation->refunded_at = now();
                $saved = $donation->save();
                
                if ($saved) {
                    echo "✓ Eloquent save() returned true\n";
                    
                    // Verify
                    $donation->refresh();
                    if ($donation->status === 'refunded') {
                        echo "✅ Eloquent update SUCCESSFUL! Status is now: {$donation->status}\n";
                    } else {
                        echo "❌ Eloquent update failed to persist! Status is still: {$donation->status}\n";
                        $issues[] = "Eloquent update() doesn't persist - possible model event blocking it";
                    }
                } else {
                    echo "❌ Eloquent save() returned false\n";
                    $issues[] = "Eloquent save() returns false - validation or model event blocking it";
                }
            } catch (\Exception $e) {
                echo "❌ Eloquent update error: {$e->getMessage()}\n";
                $issues[] = "Eloquent update throws exception: {$e->getMessage()}";
            }
        } else {
            echo "❌ Cannot find donation via Eloquent!\n";
            $issues[] = "Donation model cannot load record that exists in database";
        }
    } else {
        echo "⚠️  Skipping (no refund found to test)\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    $issues[] = "Error testing Eloquent: {$e->getMessage()}";
}

echo "\n";

// Check 6: Model Events/Observers
echo "CHECK 6: Model Events and Observers\n";
echo "-------------------------------------------\n";
$checks++;

try {
    $donation = new Donation();
    
    // Check for boot method
    if (method_exists(Donation::class, 'boot')) {
        echo "✓ Donation model has boot() method\n";
        echo "  May have event listeners that could interfere\n";
    } else {
        echo "✓ No boot() method found\n";
    }
    
    // Check for observers
    $observers = DB::table('observers')
        ->where('observable_type', 'App\\Models\\Donation')
        ->get();
    
    if ($observers->count() > 0) {
        echo "⚠️  Found {$observers->count()} observer(s) on Donation model\n";
        echo "  Observers might be blocking the status update\n";
    } else {
        echo "✓ No registered observers found\n";
    }
} catch (\Exception $e) {
    echo "⚠️  Could not check observers: {$e->getMessage()}\n";
}

echo "\n";

// Check 7: CharityRefundController
echo "CHECK 7: CharityRefundController Implementation\n";
echo "-------------------------------------------\n";
$checks++;

$controllerPath = __DIR__ . '/app/Http/Controllers/CharityRefundController.php';
if (file_exists($controllerPath)) {
    echo "✓ CharityRefundController.php exists\n";
    
    $content = file_get_contents($controllerPath);
    
    if (strpos($content, 'status\' => \'refunded\'') !== false) {
        echo "✓ Controller has code to set status='refunded'\n";
    } else {
        echo "❌ Controller does NOT set status='refunded'!\n";
        $issues[] = "CharityRefundController::respond() doesn't set status='refunded'";
    }
    
    if (strpos($content, 'is_refunded\' => true') !== false) {
        echo "✓ Controller has code to set is_refunded=true\n";
    } else {
        echo "❌ Controller does NOT set is_refunded=true!\n";
        $issues[] = "CharityRefundController::respond() doesn't set is_refunded=true";
    }
    
    // Check for error handling
    if (strpos($content, 'try') !== false && strpos($content, 'catch') !== false) {
        echo "✓ Controller has try-catch error handling\n";
    } else {
        echo "⚠️  Controller lacks try-catch error handling\n";
        echo "  Errors might be happening silently\n";
    }
} else {
    echo "❌ CharityRefundController.php NOT found!\n";
    $issues[] = "CharityRefundController.php file is missing";
}

echo "\n";

// Check 8: Check migration status
echo "CHECK 8: Migration Status\n";
echo "-------------------------------------------\n";
$checks++;

try {
    $refundMigrations = [
        '2025_11_08_000001_add_refund_fields_to_donations_table',
        '2025_11_08_150000_add_refunded_status_to_donations'
    ];
    
    foreach ($refundMigrations as $migration) {
        $exists = DB::table('migrations')->where('migration', $migration)->exists();
        
        if ($exists) {
            echo "✓ Migration '{$migration}' has been run\n";
        } else {
            echo "❌ Migration '{$migration}' NOT run!\n";
            $issues[] = "Migration '{$migration}' has not been executed";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error checking migrations: {$e->getMessage()}\n";
    $issues[] = "Cannot check migration status: {$e->getMessage()}";
}

echo "\n";

// Check 9: Application cache
echo "CHECK 9: Application Cache\n";
echo "-------------------------------------------\n";
$checks++;

$cacheFiles = [
    'bootstrap/cache/config.php',
    'bootstrap/cache/routes-v7.php',
    'bootstrap/cache/services.php'
];

$cachedFiles = 0;
foreach ($cacheFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $cachedFiles++;
    }
}

if ($cachedFiles > 0) {
    echo "⚠️  Found {$cachedFiles} cached file(s)\n";
    echo "  Cached files might contain old schema information\n";
    echo "  Run: php artisan config:clear && php artisan cache:clear\n";
} else {
    echo "✓ No cache files found\n";
}

echo "\n";

// SUMMARY
echo "==============================================\n";
echo "DIAGNOSIS SUMMARY\n";
echo "==============================================\n";
echo "Checks performed: {$checks}\n";
echo "Issues found: " . count($issues) . "\n\n";

if (count($issues) === 0) {
    echo "✅ No critical issues found!\n";
    echo "The refund system should be working correctly.\n";
    echo "If changes still don't appear:\n";
    echo "  1. Clear Laravel cache: php artisan cache:clear\n";
    echo "  2. Clear config cache: php artisan config:clear\n";
    echo "  3. Restart Laravel server\n";
    echo "  4. Hard refresh browser (Ctrl+F5)\n\n";
} else {
    echo "❌ FOUND " . count($issues) . " ISSUE(S):\n\n";
    
    foreach ($issues as $i => $issue) {
        echo ($i + 1) . ". {$issue}\n";
    }
    
    echo "\n";
    echo "RECOMMENDED ACTIONS:\n";
    
    // Prioritize migration issues
    $migrationIssues = array_filter($issues, function($issue) {
        return stripos($issue, 'migration') !== false || stripos($issue, 'ENUM') !== false;
    });
    
    if (!empty($migrationIssues)) {
        echo "\n🔧 CRITICAL: Run migrations first:\n";
        echo "   cd capstone_backend\n";
        echo "   php artisan migrate\n\n";
    }
    
    // Check for controller issues
    $controllerIssues = array_filter($issues, function($issue) {
        return stripos($issue, 'Controller') !== false;
    });
    
    if (!empty($controllerIssues)) {
        echo "\n🔧 FIX: Update CharityRefundController.php\n";
        echo "   The controller needs to properly set status='refunded'\n\n";
    }
}

echo "==============================================\n\n";
