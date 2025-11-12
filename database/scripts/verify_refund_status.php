<?php

/**
 * Verify Refund Status Script
 * 
 * This script checks the current state of refunds without making any changes
 * It will report:
 * 1. All approved refunds and their donation status
 * 2. Any inconsistencies found
 * 3. Current campaign and charity totals
 * 
 * Run this script from the capstone_backend directory:
 * php database/scripts/verify_refund_status.php
 */

require __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\{RefundRequest, Donation, Campaign, Charity, User};

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "==============================================\n";
echo "   REFUND STATUS VERIFICATION REPORT\n";
echo "==============================================\n\n";

try {
    // Check if refunded status exists in donations table
    echo "Checking database schema...\n";
    $statusColumn = DB::select("SHOW COLUMNS FROM donations WHERE Field = 'status'");
    if (!empty($statusColumn)) {
        $enumValues = $statusColumn[0]->Type;
        echo "✅ Donations table 'status' column: {$enumValues}\n";
        
        if (stripos($enumValues, 'refunded') !== false) {
            echo "✅ 'refunded' status is available\n";
        } else {
            echo "⚠️  'refunded' status is NOT in the ENUM - migration may not be run\n";
        }
    }
    
    $isRefundedColumn = DB::select("SHOW COLUMNS FROM donations WHERE Field = 'is_refunded'");
    if (!empty($isRefundedColumn)) {
        echo "✅ 'is_refunded' column exists\n";
    } else {
        echo "⚠️  'is_refunded' column is missing - migration may not be run\n";
    }
    
    echo "\n";
    
    // Get all refund requests
    $allRefunds = RefundRequest::with(['donation.campaign', 'donation.charity', 'user'])
        ->orderBy('created_at', 'desc')
        ->get();
    
    $refundStats = [
        'total' => $allRefunds->count(),
        'pending' => $allRefunds->where('status', 'pending')->count(),
        'approved' => $allRefunds->where('status', 'approved')->count(),
        'denied' => $allRefunds->where('status', 'denied')->count(),
        'cancelled' => $allRefunds->where('status', 'cancelled')->count(),
    ];
    
    echo "==============================================\n";
    echo "REFUND REQUEST SUMMARY\n";
    echo "==============================================\n";
    echo "Total Refund Requests: {$refundStats['total']}\n";
    echo "  - Pending: {$refundStats['pending']}\n";
    echo "  - Approved: {$refundStats['approved']}\n";
    echo "  - Denied: {$refundStats['denied']}\n";
    echo "  - Cancelled: {$refundStats['cancelled']}\n\n";
    
    // Check approved refunds in detail
    $approvedRefunds = $allRefunds->where('status', 'approved');
    
    if ($approvedRefunds->count() > 0) {
        echo "==============================================\n";
        echo "APPROVED REFUNDS DETAILS\n";
        echo "==============================================\n\n";
        
        $inconsistencies = 0;
        
        foreach ($approvedRefunds as $refund) {
            $donation = $refund->donation;
            
            if (!$donation) {
                echo "⚠️  Refund ID {$refund->id}: Donation not found (deleted?)\n\n";
                continue;
            }
            
            $isInconsistent = ($donation->status !== 'refunded' || !$donation->is_refunded);
            
            if ($isInconsistent) {
                $inconsistencies++;
                echo "❌ INCONSISTENCY FOUND:\n";
            } else {
                echo "✅ Refund ID: {$refund->id}\n";
            }
            
            echo "  - Donation ID: {$donation->id}\n";
            echo "  - Donor: ";
            
            if ($refund->user) {
                echo "{$refund->user->name} ({$refund->user->email})";
            } else {
                echo "User ID {$refund->user_id} (not found)";
            }
            echo "\n";
            
            echo "  - Amount: ₱" . number_format($donation->amount, 2) . "\n";
            echo "  - Donation Status: {$donation->status}";
            
            if ($donation->status !== 'refunded') {
                echo " ⚠️  SHOULD BE 'refunded'";
            }
            echo "\n";
            
            echo "  - Is Refunded Flag: " . ($donation->is_refunded ? 'true' : 'false');
            
            if (!$donation->is_refunded) {
                echo " ⚠️  SHOULD BE true";
            }
            echo "\n";
            
            echo "  - Refunded At: " . ($donation->refunded_at ? $donation->refunded_at->format('Y-m-d H:i:s') : 'NOT SET ⚠️') . "\n";
            
            if ($donation->campaign) {
                echo "  - Campaign: {$donation->campaign->title} (ID: {$donation->campaign_id})\n";
            }
            
            if ($donation->charity) {
                echo "  - Charity: {$donation->charity->name} (ID: {$donation->charity_id})\n";
            }
            
            echo "\n";
        }
        
        if ($inconsistencies > 0) {
            echo "==============================================\n";
            echo "⚠️  {$inconsistencies} INCONSISTENCIES FOUND\n";
            echo "==============================================\n";
            echo "These approved refunds have donations that are not\n";
            echo "properly marked as refunded. Run the fix script:\n";
            echo "  php database/scripts/fix_refund_donations.php\n\n";
        } else {
            echo "==============================================\n";
            echo "✅ ALL APPROVED REFUNDS ARE CONSISTENT\n";
            echo "==============================================\n\n";
        }
    } else {
        echo "No approved refunds found in the system.\n\n";
    }
    
    // Check for donations with is_refunded=true but status not 'refunded'
    $inconsistentDonations = Donation::where('is_refunded', true)
        ->where('status', '!=', 'refunded')
        ->with(['campaign', 'charity', 'donor'])
        ->get();
    
    if ($inconsistentDonations->count() > 0) {
        echo "==============================================\n";
        echo "⚠️  DONATIONS WITH INCONSISTENT REFUND FLAGS\n";
        echo "==============================================\n";
        echo "Found {$inconsistentDonations->count()} donations with is_refunded=true\n";
        echo "but status is not 'refunded':\n\n";
        
        foreach ($inconsistentDonations as $donation) {
            echo "Donation ID: {$donation->id}\n";
            echo "  - Status: {$donation->status} (should be 'refunded')\n";
            echo "  - Amount: ₱" . number_format($donation->amount, 2) . "\n";
            
            if ($donation->donor) {
                echo "  - Donor: {$donation->donor->name}\n";
            }
            
            if ($donation->campaign) {
                echo "  - Campaign: {$donation->campaign->title}\n";
            }
            
            echo "\n";
        }
    }
    
    // Summary statistics
    $totalDonations = Donation::count();
    $completedDonations = Donation::where('status', 'completed')->count();
    $refundedDonations = Donation::where('status', 'refunded')->count();
    $refundedByFlag = Donation::where('is_refunded', true)->count();
    
    echo "==============================================\n";
    echo "DONATION STATISTICS\n";
    echo "==============================================\n";
    echo "Total Donations: {$totalDonations}\n";
    echo "Completed Donations: {$completedDonations}\n";
    echo "Refunded Donations (by status): {$refundedDonations}\n";
    echo "Refunded Donations (by flag): {$refundedByFlag}\n\n";
    
    if ($refundedDonations !== $refundedByFlag) {
        echo "⚠️  Mismatch between status count and flag count!\n";
        echo "This indicates data inconsistency.\n\n";
    }
    
    echo "==============================================\n";
    echo "✅ VERIFICATION COMPLETE\n";
    echo "==============================================\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR OCCURRED:\n";
    echo $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    
    exit(1);
}
