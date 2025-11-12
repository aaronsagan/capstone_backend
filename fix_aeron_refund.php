<?php

/**
 * Fix Aeron's Approved Refund (₱2,070.00)
 * 
 * This script fixes the specific refund visible in the screenshots:
 * - Refund ID: #3
 * - Amount: ₱2,070.00
 * - Donor: Aeron Mendoza Baguiu
 * - Campaign: IFL (Integrated Foundational Learning)
 * - Status: Approved but donation still shows as "Completed"
 * 
 * Run: php fix_aeron_refund.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\{Donation, Campaign, Charity};

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "==============================================\n";
echo "   FIXING AERON'S REFUND (₱2,070.00)\n";
echo "==============================================\n\n";

try {
    // Start transaction
    DB::beginTransaction();
    
    // Find the specific refund (₱2,070.00, Aeron, approved)
    echo "Step 1: Locating the refund in database...\n";
    
    $refund = DB::table('refund_requests as rr')
        ->join('donations as d', 'rr.donation_id', '=', 'd.id')
        ->join('users as u', 'rr.user_id', '=', 'u.id')
        ->leftJoin('campaigns as c', 'd.campaign_id', '=', 'c.id')
        ->leftJoin('charities as ch', 'd.charity_id', '=', 'ch.id')
        ->where('d.amount', 2070.00)
        ->where('rr.status', 'approved')
        ->where('u.email', 'like', '%aeron%')
        ->select(
            'rr.id as refund_id',
            'rr.status as refund_status',
            'rr.reviewed_at',
            'd.id as donation_id',
            'd.status as donation_status',
            'd.is_refunded',
            'd.refunded_at',
            'd.amount',
            'd.campaign_id',
            'd.charity_id',
            'u.id as user_id',
            'u.name as donor_name',
            'u.email as donor_email',
            'c.title as campaign_title',
            'c.total_donations_received as campaign_total_before',
            'ch.name as charity_name',
            'ch.total_donations_received as charity_total_before'
        )
        ->first();
    
    if (!$refund) {
        echo "❌ ERROR: Could not find the refund in database.\n";
        echo "   Looking for:\n";
        echo "   - Amount: ₱2,070.00\n";
        echo "   - Status: Approved\n";
        echo "   - Donor email containing 'aeron'\n\n";
        
        // Try to find any refunds for this amount
        $anyRefund = DB::table('refund_requests as rr')
            ->join('donations as d', 'rr.donation_id', '=', 'd.id')
            ->where('d.amount', 2070.00)
            ->select('rr.id', 'rr.status', 'd.status as donation_status')
            ->first();
        
        if ($anyRefund) {
            echo "   Found refund with this amount but different criteria:\n";
            echo "   - Refund ID: {$anyRefund->id}\n";
            echo "   - Refund Status: {$anyRefund->status}\n";
            echo "   - Donation Status: {$anyRefund->donation_status}\n";
        } else {
            echo "   No refund with amount ₱2,070.00 found at all.\n";
        }
        
        DB::rollBack();
        exit(1);
    }
    
    echo "✓ Found refund in database:\n";
    echo "  Refund ID: {$refund->refund_id}\n";
    echo "  Donor: {$refund->donor_name} ({$refund->donor_email})\n";
    echo "  Amount: ₱" . number_format($refund->amount, 2) . "\n";
    echo "  Campaign: {$refund->campaign_title}\n";
    echo "  Charity: {$refund->charity_name}\n\n";
    
    echo "  Current Status:\n";
    echo "    Refund Status: {$refund->refund_status}\n";
    echo "    Donation Status: {$refund->donation_status}";
    
    if ($refund->donation_status !== 'refunded') {
        echo " ❌ NEEDS FIX\n";
    } else {
        echo " ✅ Already correct\n";
    }
    
    echo "    Is Refunded Flag: " . ($refund->is_refunded ? 'true' : 'false');
    
    if (!$refund->is_refunded) {
        echo " ❌ NEEDS FIX\n";
    } else {
        echo " ✅ Already correct\n";
    }
    
    echo "\n";
    
    // Check if fix is needed
    if ($refund->donation_status === 'refunded' && $refund->is_refunded) {
        echo "✓ This refund is already correctly processed!\n";
        echo "  No changes needed.\n\n";
        DB::rollBack();
        exit(0);
    }
    
    // Perform the fix
    echo "Step 2: Updating donation status...\n";
    
    $donation = Donation::find($refund->donation_id);
    
    if (!$donation) {
        echo "❌ ERROR: Donation not found.\n";
        DB::rollBack();
        exit(1);
    }
    
    // Store original values
    $originalStatus = $donation->status;
    $originalIsRefunded = $donation->is_refunded;
    
    // Update donation
    $donation->update([
        'status' => 'refunded',
        'is_refunded' => true,
        'refunded_at' => $refund->reviewed_at ?? now(),
    ]);
    
    // Verify update
    $donation->refresh();
    
    if ($donation->status !== 'refunded' || !$donation->is_refunded) {
        echo "❌ ERROR: Failed to update donation status.\n";
        DB::rollBack();
        exit(1);
    }
    
    echo "✓ Donation updated successfully:\n";
    echo "  Status: {$originalStatus} → {$donation->status}\n";
    echo "  Is Refunded: " . ($originalIsRefunded ? 'true' : 'false') . " → true\n";
    echo "  Refunded At: {$donation->refunded_at}\n\n";
    
    // Recalculate campaign totals
    if ($refund->campaign_id) {
        echo "Step 3: Recalculating campaign totals...\n";
        
        $campaign = Campaign::find($refund->campaign_id);
        
        if ($campaign) {
            $campaign->recalculateTotals();
            $campaign->refresh();
            
            $newTotal = $campaign->total_donations_received;
            $difference = $refund->campaign_total_before - $newTotal;
            
            echo "✓ Campaign: {$refund->campaign_title}\n";
            echo "  Total Before: ₱" . number_format($refund->campaign_total_before, 2) . "\n";
            echo "  Total After: ₱" . number_format($newTotal, 2) . "\n";
            echo "  Reduced By: ₱" . number_format($difference, 2) . "\n\n";
        }
    }
    
    // Recalculate charity totals
    if ($refund->charity_id) {
        echo "Step 4: Recalculating charity totals...\n";
        
        $charity = Charity::find($refund->charity_id);
        
        if ($charity) {
            $charity->recalculateTotals();
            $charity->refresh();
            
            $newTotal = $charity->total_donations_received;
            $difference = $refund->charity_total_before - $newTotal;
            
            echo "✓ Charity: {$refund->charity_name}\n";
            echo "  Total Before: ₱" . number_format($refund->charity_total_before, 2) . "\n";
            echo "  Total After: ₱" . number_format($newTotal, 2) . "\n";
            echo "  Reduced By: ₱" . number_format($difference, 2) . "\n\n";
        }
    }
    
    // Commit transaction
    DB::commit();
    
    echo "==============================================\n";
    echo "✅ FIX COMPLETED SUCCESSFULLY!\n";
    echo "==============================================\n";
    echo "Summary:\n";
    echo "  - Donation status changed to 'refunded'\n";
    echo "  - is_refunded flag set to true\n";
    echo "  - refunded_at timestamp set\n";
    echo "  - Campaign total reduced by ₱" . number_format($refund->amount, 2) . "\n";
    echo "  - Charity total reduced\n\n";
    echo "The donation will now show as 'Refunded' in:\n";
    echo "  ✓ Donor's donation history\n";
    echo "  ✓ Charity's donation list\n";
    echo "  ✓ Campaign statistics\n\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    
    echo "\n❌ ERROR OCCURRED:\n";
    echo $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    
    exit(1);
}
