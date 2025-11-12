<?php

/**
 * Fix Refund Donations Script
 * 
 * This script fixes donations that have approved refunds but incorrect status
 * It will:
 * 1. Find all approved refunds where donation status is not 'refunded'
 * 2. Update donation status to 'refunded' and set is_refunded=true
 * 3. Recalculate all affected campaign and charity totals
 * 4. Log all changes made
 * 
 * Run this script from the capstone_backend directory:
 * php database/scripts/fix_refund_donations.php
 */

require __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\{RefundRequest, Donation, Campaign, Charity};

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "==============================================\n";
echo "   REFUND DONATIONS FIX SCRIPT\n";
echo "==============================================\n\n";

try {
    // Start transaction
    DB::beginTransaction();
    
    // Find all approved refunds where donation status is not 'refunded'
    $problematicRefunds = RefundRequest::where('status', 'approved')
        ->whereHas('donation', function($query) {
            $query->where('status', '!=', 'refunded')
                  ->orWhere('is_refunded', false);
        })
        ->with(['donation.campaign', 'donation.charity', 'user'])
        ->get();
    
    $fixCount = 0;
    $affectedCampaigns = [];
    $affectedCharities = [];
    
    echo "Found " . $problematicRefunds->count() . " problematic refunds\n\n";
    
    foreach ($problematicRefunds as $refund) {
        $donation = $refund->donation;
        
        if (!$donation) {
            echo "⚠️  Refund ID {$refund->id}: Donation not found (deleted?)\n";
            continue;
        }
        
        echo "Processing Refund ID: {$refund->id}\n";
        echo "  - Donation ID: {$donation->id}\n";
        echo "  - Donor: {$refund->user->name} (ID: {$refund->user_id})\n";
        echo "  - Amount: ₱" . number_format($donation->amount, 2) . "\n";
        echo "  - Current Status: {$donation->status}\n";
        echo "  - Is Refunded Flag: " . ($donation->is_refunded ? 'true' : 'false') . "\n";
        
        // Store old values
        $oldStatus = $donation->status;
        $oldIsRefunded = $donation->is_refunded;
        
        // Update donation
        $donation->update([
            'status' => 'refunded',
            'is_refunded' => true,
            'refunded_at' => $refund->reviewed_at ?? now(),
        ]);
        
        $donation->refresh();
        
        echo "  ✅ Updated to: status='{$donation->status}', is_refunded=" . ($donation->is_refunded ? 'true' : 'false') . "\n";
        
        // Track affected campaigns and charities
        if ($donation->campaign_id) {
            $affectedCampaigns[$donation->campaign_id] = $donation->campaign;
        }
        if ($donation->charity_id) {
            $affectedCharities[$donation->charity_id] = $donation->charity;
        }
        
        $fixCount++;
        echo "\n";
    }
    
    // Recalculate all affected campaign totals
    if (!empty($affectedCampaigns)) {
        echo "==============================================\n";
        echo "Recalculating Campaign Totals...\n";
        echo "==============================================\n\n";
        
        foreach ($affectedCampaigns as $campaignId => $campaign) {
            if ($campaign) {
                $oldTotal = $campaign->total_donations_received;
                $oldDonors = $campaign->donors_count;
                
                $campaign->recalculateTotals();
                $campaign->refresh();
                
                $newTotal = $campaign->total_donations_received;
                $newDonors = $campaign->donors_count;
                
                echo "Campaign: {$campaign->title} (ID: {$campaignId})\n";
                echo "  - Total Raised: ₱" . number_format($oldTotal, 2) . " → ₱" . number_format($newTotal, 2);
                
                if ($oldTotal != $newTotal) {
                    $diff = $oldTotal - $newTotal;
                    echo " (reduced by ₱" . number_format($diff, 2) . ")";
                }
                echo "\n";
                
                echo "  - Donor Count: {$oldDonors} → {$newDonors}\n\n";
            }
        }
    }
    
    // Recalculate all affected charity totals
    if (!empty($affectedCharities)) {
        echo "==============================================\n";
        echo "Recalculating Charity Totals...\n";
        echo "==============================================\n\n";
        
        foreach ($affectedCharities as $charityId => $charity) {
            if ($charity) {
                $oldTotal = $charity->total_donations_received;
                $oldDonors = $charity->donors_count;
                
                $charity->recalculateTotals();
                $charity->refresh();
                
                $newTotal = $charity->total_donations_received;
                $newDonors = $charity->donors_count;
                
                echo "Charity: {$charity->name} (ID: {$charityId})\n";
                echo "  - Total Raised: ₱" . number_format($oldTotal, 2) . " → ₱" . number_format($newTotal, 2);
                
                if ($oldTotal != $newTotal) {
                    $diff = $oldTotal - $newTotal;
                    echo " (reduced by ₱" . number_format($diff, 2) . ")";
                }
                echo "\n";
                
                echo "  - Donor Count: {$oldDonors} → {$newDonors}\n\n";
            }
        }
    }
    
    // Commit transaction
    DB::commit();
    
    echo "==============================================\n";
    echo "✅ FIX COMPLETE\n";
    echo "==============================================\n";
    echo "Donations Fixed: {$fixCount}\n";
    echo "Campaigns Updated: " . count($affectedCampaigns) . "\n";
    echo "Charities Updated: " . count($affectedCharities) . "\n";
    echo "\nAll changes have been committed to the database.\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    
    echo "\n❌ ERROR OCCURRED:\n";
    echo $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    echo "\nAll changes have been rolled back.\n";
    
    exit(1);
}
