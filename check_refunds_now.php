<?php

/**
 * Quick Check: Approved Refunds Database Status
 * 
 * This script quickly checks all approved refunds in the database
 * and shows which ones have incorrect donation status.
 * 
 * Run from capstone_backend directory:
 * php check_refunds_now.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "==============================================\n";
echo "   CHECKING APPROVED REFUNDS IN DATABASE\n";
echo "==============================================\n\n";

try {
    // Query approved refunds with donation details
    $refunds = DB::table('refund_requests as rr')
        ->join('donations as d', 'rr.donation_id', '=', 'd.id')
        ->join('users as u', 'rr.user_id', '=', 'u.id')
        ->leftJoin('campaigns as c', 'd.campaign_id', '=', 'c.id')
        ->leftJoin('charities as ch', 'd.charity_id', '=', 'ch.id')
        ->where('rr.status', 'approved')
        ->select(
            'rr.id as refund_id',
            'rr.status as refund_status',
            'rr.reviewed_at',
            'rr.refund_amount',
            'd.id as donation_id',
            'd.status as donation_status',
            'd.is_refunded',
            'd.refunded_at',
            'd.amount as donation_amount',
            'u.name as donor_name',
            'u.email as donor_email',
            'c.title as campaign_title',
            'c.id as campaign_id',
            'c.total_donations_received as campaign_total',
            'ch.name as charity_name',
            'ch.id as charity_id',
            'ch.total_donations_received as charity_total'
        )
        ->orderBy('rr.reviewed_at', 'desc')
        ->get();
    
    if ($refunds->isEmpty()) {
        echo "ℹ️  No approved refunds found in the database.\n\n";
        exit(0);
    }
    
    echo "Found " . $refunds->count() . " approved refund(s)\n\n";
    
    $problemCount = 0;
    
    foreach ($refunds as $refund) {
        $hasProblem = false;
        
        // Check if donation status is correct
        if ($refund->donation_status !== 'refunded') {
            $hasProblem = true;
        }
        
        // Check if is_refunded flag is set
        if (!$refund->is_refunded) {
            $hasProblem = true;
        }
        
        if ($hasProblem) {
            $problemCount++;
            echo "❌ PROBLEM FOUND:\n";
        } else {
            echo "✅ OK:\n";
        }
        
        echo "   Refund ID: {$refund->refund_id}\n";
        echo "   Donor: {$refund->donor_name} ({$refund->donor_email})\n";
        echo "   Amount: ₱" . number_format($refund->donation_amount, 2) . "\n";
        
        if ($refund->campaign_title) {
            echo "   Campaign: {$refund->campaign_title} (ID: {$refund->campaign_id})\n";
            echo "   Campaign Total: ₱" . number_format($refund->campaign_total, 2) . "\n";
        }
        
        if ($refund->charity_name) {
            echo "   Charity: {$refund->charity_name} (ID: {$refund->charity_id})\n";
        }
        
        echo "   Donation ID: {$refund->donation_id}\n";
        echo "   Donation Status: {$refund->donation_status}";
        
        if ($refund->donation_status !== 'refunded') {
            echo " ⚠️  SHOULD BE 'refunded'";
        }
        echo "\n";
        
        echo "   Is Refunded Flag: " . ($refund->is_refunded ? 'true' : 'false');
        
        if (!$refund->is_refunded) {
            echo " ⚠️  SHOULD BE true";
        }
        echo "\n";
        
        echo "   Refunded At: " . ($refund->refunded_at ?? 'NOT SET ⚠️') . "\n";
        echo "   Approved At: {$refund->reviewed_at}\n";
        
        echo "\n";
    }
    
    // Summary
    echo "==============================================\n";
    echo "SUMMARY\n";
    echo "==============================================\n";
    echo "Total Approved Refunds: " . $refunds->count() . "\n";
    echo "Problems Found: {$problemCount}\n";
    echo "Correct: " . ($refunds->count() - $problemCount) . "\n\n";
    
    if ($problemCount > 0) {
        echo "⚠️  {$problemCount} refund(s) have incorrect donation status!\n\n";
        echo "These refunds were approved but the donations are still\n";
        echo "marked as 'completed' instead of 'refunded', which means:\n";
        echo "  - Campaign totals are NOT reduced\n";
        echo "  - Charity totals are NOT reduced\n";
        echo "  - Donors see wrong status in their history\n\n";
        echo "TO FIX THIS, run:\n";
        echo "  php database/scripts/fix_refund_donations.php\n\n";
        echo "Or use PowerShell:\n";
        echo "  cd ..\n";
        echo "  .\\fix-refund-donations.ps1\n\n";
    } else {
        echo "✅ All approved refunds are correctly processed!\n";
        echo "All donation statuses are set to 'refunded' and\n";
        echo "campaign/charity totals should be accurate.\n\n";
    }
    
    // Additional check: campaigns with potential issues
    echo "==============================================\n";
    echo "CAMPAIGN TOTALS VERIFICATION\n";
    echo "==============================================\n\n";
    
    $campaignsWithRefunds = DB::table('campaigns as c')
        ->join('donations as d', 'c.id', '=', 'd.campaign_id')
        ->join('refund_requests as rr', 'd.id', '=', 'rr.donation_id')
        ->where('rr.status', 'approved')
        ->select('c.id', 'c.title', 'c.total_donations_received')
        ->selectRaw('
            (SELECT COALESCE(SUM(d2.amount), 0) 
             FROM donations d2 
             WHERE d2.campaign_id = c.id 
               AND d2.status = "completed" 
               AND d2.is_refunded = 0) as calculated_total
        ')
        ->selectRaw('COUNT(DISTINCT rr.id) as refunded_count')
        ->groupBy('c.id', 'c.title', 'c.total_donations_received')
        ->get();
    
    foreach ($campaignsWithRefunds as $campaign) {
        $difference = $campaign->total_donations_received - $campaign->calculated_total;
        
        if (abs($difference) > 0.01) {
            echo "⚠️  {$campaign->title}\n";
            echo "   Current Total: ₱" . number_format($campaign->total_donations_received, 2) . "\n";
            echo "   Should Be: ₱" . number_format($campaign->calculated_total, 2) . "\n";
            echo "   Difference: ₱" . number_format($difference, 2) . " (needs recalculation)\n";
            echo "   Refunded Donations: {$campaign->refunded_count}\n\n";
        } else {
            echo "✅ {$campaign->title}\n";
            echo "   Total: ₱" . number_format($campaign->total_donations_received, 2) . " (correct)\n";
            echo "   Refunded Donations: {$campaign->refunded_count}\n\n";
        }
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERROR OCCURRED:\n";
    echo $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "==============================================\n";
echo "Check complete!\n";
echo "==============================================\n\n";
