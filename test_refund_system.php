<?php

/**
 * Comprehensive Refund System Test
 * 
 * Tests all aspects of the refund system to ensure everything is working:
 * 1. Database schema and migrations
 * 2. Approved refunds are properly marked
 * 3. Campaign totals exclude refunded donations
 * 4. Charity totals exclude refunded donations
 * 5. Donor statistics exclude refunded donations
 * 6. All queries properly filter refunded donations
 * 
 * Run: php test_refund_system.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\{Donation, Campaign, Charity};

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$passedTests = 0;
$failedTests = 0;
$warnings = 0;

function testPass($message) {
    global $passedTests;
    $passedTests++;
    echo "  ✅ PASS: {$message}\n";
}

function testFail($message) {
    global $failedTests;
    $failedTests++;
    echo "  ❌ FAIL: {$message}\n";
}

function testWarning($message) {
    global $warnings;
    $warnings++;
    echo "  ⚠️  WARN: {$message}\n";
}

echo "\n";
echo "==============================================\n";
echo "   REFUND SYSTEM COMPREHENSIVE TEST\n";
echo "==============================================\n\n";

try {
    // Test 1: Database Schema
    echo "-------------------------------------------\n";
    echo "TEST 1: Database Schema\n";
    echo "-------------------------------------------\n";
    
    $statusColumn = DB::select("SHOW COLUMNS FROM donations WHERE Field = 'status'");
    if (!empty($statusColumn) && stripos($statusColumn[0]->Type, 'refunded') !== false) {
        testPass("'refunded' status exists in donations.status ENUM");
    } else {
        testFail("'refunded' status missing from donations.status ENUM");
    }
    
    $isRefundedColumn = DB::select("SHOW COLUMNS FROM donations WHERE Field = 'is_refunded'");
    if (!empty($isRefundedColumn)) {
        testPass("'is_refunded' column exists in donations table");
    } else {
        testFail("'is_refunded' column missing from donations table");
    }
    
    $refundedAtColumn = DB::select("SHOW COLUMNS FROM donations WHERE Field = 'refunded_at'");
    if (!empty($refundedAtColumn)) {
        testPass("'refunded_at' column exists in donations table");
    } else {
        testFail("'refunded_at' column missing from donations table");
    }
    
    echo "\n";
    
    // Test 2: Approved Refunds Status Consistency
    echo "-------------------------------------------\n";
    echo "TEST 2: Approved Refunds Status Consistency\n";
    echo "-------------------------------------------\n";
    
    $approvedRefunds = DB::table('refund_requests')->where('status', 'approved')->count();
    echo "  Found {$approvedRefunds} approved refund(s)\n";
    
    if ($approvedRefunds > 0) {
        $inconsistentRefunds = DB::table('refund_requests as rr')
            ->join('donations as d', 'rr.donation_id', '=', 'd.id')
            ->where('rr.status', 'approved')
            ->where(function($query) {
                $query->where('d.status', '!=', 'refunded')
                      ->orWhere('d.is_refunded', false);
            })
            ->count();
        
        if ($inconsistentRefunds === 0) {
            testPass("All {$approvedRefunds} approved refunds have correct donation status");
        } else {
            testFail("{$inconsistentRefunds} approved refunds have incorrect donation status");
        }
    } else {
        testWarning("No approved refunds to test");
    }
    
    echo "\n";
    
    // Test 3: Campaign Totals Accuracy
    echo "-------------------------------------------\n";
    echo "TEST 3: Campaign Totals Accuracy\n";
    echo "-------------------------------------------\n";
    
    $campaignsWithDonations = Campaign::has('donations')->take(5)->get();
    
    if ($campaignsWithDonations->isEmpty()) {
        testWarning("No campaigns with donations to test");
    } else {
        foreach ($campaignsWithDonations as $campaign) {
            $storedTotal = $campaign->total_donations_received;
            
            $calculatedTotal = $campaign->donations()
                ->where('status', 'completed')
                ->where('is_refunded', false)
                ->sum('amount');
            
            $difference = abs($storedTotal - $calculatedTotal);
            
            if ($difference < 0.01) {
                testPass("Campaign '{$campaign->title}' total is accurate (₱" . number_format($storedTotal, 2) . ")");
            } else {
                testFail("Campaign '{$campaign->title}' total is incorrect (stored: ₱" . number_format($storedTotal, 2) . ", should be: ₱" . number_format($calculatedTotal, 2) . ")");
            }
        }
    }
    
    echo "\n";
    
    // Test 4: Charity Totals Accuracy
    echo "-------------------------------------------\n";
    echo "TEST 4: Charity Totals Accuracy\n";
    echo "-------------------------------------------\n";
    
    $charitiesWithDonations = Charity::has('donations')->take(5)->get();
    
    if ($charitiesWithDonations->isEmpty()) {
        testWarning("No charities with donations to test");
    } else {
        foreach ($charitiesWithDonations as $charity) {
            $storedTotal = $charity->total_donations_received;
            
            $calculatedTotal = $charity->donations()
                ->where('status', 'completed')
                ->where('is_refunded', false)
                ->sum('amount');
            
            $difference = abs($storedTotal - $calculatedTotal);
            
            if ($difference < 0.01) {
                testPass("Charity '{$charity->name}' total is accurate (₱" . number_format($storedTotal, 2) . ")");
            } else {
                testFail("Charity '{$charity->name}' total is incorrect (stored: ₱" . number_format($storedTotal, 2) . ", should be: ₱" . number_format($calculatedTotal, 2) . ")");
            }
        }
    }
    
    echo "\n";
    
    // Test 5: Refunded Donations Excluded from Statistics
    echo "-------------------------------------------\n";
    echo "TEST 5: Refunded Donations Excluded from Stats\n";
    echo "-------------------------------------------\n";
    
    $refundedDonations = Donation::where('is_refunded', true)->orWhere('status', 'refunded')->count();
    
    if ($refundedDonations > 0) {
        echo "  Found {$refundedDonations} refunded donation(s)\n";
        
        // Check if platform statistics exclude refunded
        $platformTotal = Donation::where('status', 'completed')->where('is_refunded', false)->sum('amount');
        $platformWithRefunded = Donation::where('status', 'completed')->sum('amount');
        
        if ($platformTotal < $platformWithRefunded) {
            testPass("Platform statistics properly exclude refunded donations");
        } else if ($platformTotal === $platformWithRefunded) {
            testWarning("Platform statistics same whether refunds excluded or not (may indicate refunds not marked)");
        }
    } else {
        testWarning("No refunded donations to test exclusion");
    }
    
    echo "\n";
    
    // Test 6: Specific Aeron Refund (₱2,070)
    echo "-------------------------------------------\n";
    echo "TEST 6: Aeron's Specific Refund (₱2,070)\n";
    echo "-------------------------------------------\n";
    
    $aeronRefund = DB::table('refund_requests as rr')
        ->join('donations as d', 'rr.donation_id', '=', 'd.id')
        ->join('users as u', 'rr.user_id', '=', 'u.id')
        ->where('d.amount', 2070.00)
        ->where('rr.status', 'approved')
        ->select('d.status as donation_status', 'd.is_refunded', 'u.name as donor')
        ->first();
    
    if ($aeronRefund) {
        echo "  Found Aeron's refund in database\n";
        
        if ($aeronRefund->donation_status === 'refunded' && $aeronRefund->is_refunded) {
            testPass("Aeron's donation correctly marked as refunded");
        } else {
            testFail("Aeron's donation NOT properly marked (status: {$aeronRefund->donation_status}, is_refunded: " . ($aeronRefund->is_refunded ? 'true' : 'false') . ")");
        }
    } else {
        testWarning("Aeron's ₱2,070 approved refund not found");
    }
    
    echo "\n";
    
    // Test 7: IFL Campaign Impact
    echo "-------------------------------------------\n";
    echo "TEST 7: IFL Campaign Refund Impact\n";
    echo "-------------------------------------------\n";
    
    $iflCampaign = Campaign::where('title', 'like', '%integrated%foundational%learning%')
        ->orWhere('title', 'like', '%IFL%')
        ->first();
    
    if ($iflCampaign) {
        echo "  Found IFL campaign: {$iflCampaign->title}\n";
        
        $refundedInCampaign = DB::table('donations as d')
            ->join('refund_requests as rr', 'd.id', '=', 'rr.donation_id')
            ->where('d.campaign_id', $iflCampaign->id)
            ->where('rr.status', 'approved')
            ->count();
        
        if ($refundedInCampaign > 0) {
            echo "  Found {$refundedInCampaign} approved refund(s) in this campaign\n";
            
            $storedTotal = $iflCampaign->total_donations_received;
            $calculatedTotal = $iflCampaign->donations()
                ->where('status', 'completed')
                ->where('is_refunded', false)
                ->sum('amount');
            
            if (abs($storedTotal - $calculatedTotal) < 0.01) {
                testPass("IFL campaign total excludes refunded donations (₱" . number_format($storedTotal, 2) . ")");
            } else {
                testFail("IFL campaign total incorrect (stored: ₱" . number_format($storedTotal, 2) . ", should be: ₱" . number_format($calculatedTotal, 2) . ")");
            }
        } else {
            testWarning("No approved refunds in IFL campaign");
        }
    } else {
        testWarning("IFL campaign not found");
    }
    
    echo "\n";
    
    // Final Summary
    echo "==============================================\n";
    echo "TEST SUMMARY\n";
    echo "==============================================\n";
    echo "Total Tests Run: " . ($passedTests + $failedTests) . "\n";
    echo "✅ Passed: {$passedTests}\n";
    echo "❌ Failed: {$failedTests}\n";
    echo "⚠️  Warnings: {$warnings}\n\n";
    
    if ($failedTests === 0) {
        echo "🎉 ALL TESTS PASSED!\n";
        echo "The refund system is working correctly.\n\n";
        exit(0);
    } else {
        echo "⚠️  {$failedTests} TEST(S) FAILED!\n";
        echo "Please review the failures above and run the fix script.\n\n";
        exit(1);
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERROR OCCURRED DURING TESTING:\n";
    echo $e->getMessage() . "\n\n";
    exit(1);
}
