<?php

/**
 * Check Specific Refunds from Screenshot
 * 
 * Checks the refunds shown in the charity refund page screenshot:
 * - ₱7,070.00 refund (Approved - November 8)
 * - ₱375.00 refund for "Green Earth Movement" campaign
 * 
 * Run: php check_specific_refunds.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "==============================================\n";
echo "   CHECKING SPECIFIC REFUNDS FROM SCREENSHOT\n";
echo "==============================================\n\n";

try {
    // Find refunds matching the amounts from screenshot
    echo "Looking for refunds matching screenshot amounts...\n\n";
    
    // Check for ₱7,070.00 refund
    echo "-------------------------------------------\n";
    echo "CHECKING: ₱7,070.00 REFUND (Approved)\n";
    echo "-------------------------------------------\n";
    
    $refund7070 = DB::table('refund_requests as rr')
        ->join('donations as d', 'rr.donation_id', '=', 'd.id')
        ->join('users as u', 'rr.user_id', '=', 'u.id')
        ->leftJoin('campaigns as c', 'd.campaign_id', '=', 'c.id')
        ->leftJoin('charities as ch', 'd.charity_id', '=', 'ch.id')
        ->where('d.amount', 7070.00)
        ->whereIn('rr.status', ['approved', 'pending'])
        ->select(
            'rr.id as refund_id',
            'rr.status as refund_status',
            'rr.reviewed_at',
            'd.id as donation_id',
            'd.status as donation_status',
            'd.is_refunded',
            'd.refunded_at',
            'd.amount',
            'd.donated_at',
            'u.name as donor_name',
            'u.email as donor_email',
            'c.id as campaign_id',
            'c.title as campaign_title',
            'c.total_donations_received as campaign_total',
            'ch.name as charity_name',
            'ch.total_donations_received as charity_total'
        )
        ->first();
    
    if ($refund7070) {
        echo "✓ Found refund in database:\n";
        echo "  Refund ID: {$refund7070->refund_id}\n";
        echo "  Refund Status: {$refund7070->refund_status}\n";
        echo "  Donor: {$refund7070->donor_name} ({$refund7070->donor_email})\n";
        echo "  Amount: ₱" . number_format($refund7070->amount, 2) . "\n";
        echo "  Donated At: {$refund7070->donated_at}\n";
        
        if ($refund7070->campaign_title) {
            echo "  Campaign: {$refund7070->campaign_title} (ID: {$refund7070->campaign_id})\n";
            echo "  Campaign Total: ₱" . number_format($refund7070->campaign_total, 2) . "\n";
        }
        
        if ($refund7070->charity_name) {
            echo "  Charity: {$refund7070->charity_name}\n";
            echo "  Charity Total: ₱" . number_format($refund7070->charity_total, 2) . "\n";
        }
        
        echo "\n  Donation Details:\n";
        echo "    Donation ID: {$refund7070->donation_id}\n";
        echo "    Donation Status: {$refund7070->donation_status}";
        
        if ($refund7070->refund_status === 'approved') {
            if ($refund7070->donation_status !== 'refunded') {
                echo " ❌ PROBLEM: Should be 'refunded'\n";
            } else {
                echo " ✅ Correct\n";
            }
        } else {
            echo " (Refund still pending)\n";
        }
        
        echo "    Is Refunded Flag: " . ($refund7070->is_refunded ? 'true' : 'false');
        
        if ($refund7070->refund_status === 'approved') {
            if (!$refund7070->is_refunded) {
                echo " ❌ PROBLEM: Should be true\n";
            } else {
                echo " ✅ Correct\n";
            }
        } else {
            echo " (Refund still pending)\n";
        }
        
        echo "    Refunded At: " . ($refund7070->refunded_at ?? 'NOT SET');
        
        if ($refund7070->refund_status === 'approved' && !$refund7070->refunded_at) {
            echo " ⚠️ Should have timestamp";
        }
        echo "\n";
        
        // Check if this donation is being counted in campaign total
        if ($refund7070->campaign_id && $refund7070->refund_status === 'approved') {
            echo "\n  Campaign Impact Analysis:\n";
            
            // Calculate what campaign total should be
            $correctTotal = DB::table('donations')
                ->where('campaign_id', $refund7070->campaign_id)
                ->where('status', 'completed')
                ->where('is_refunded', false)
                ->sum('amount');
            
            echo "    Current Campaign Total: ₱" . number_format($refund7070->campaign_total, 2) . "\n";
            echo "    Calculated Total (excluding refunds): ₱" . number_format($correctTotal, 2) . "\n";
            
            $difference = $refund7070->campaign_total - $correctTotal;
            
            if (abs($difference) > 0.01) {
                echo "    Difference: ₱" . number_format($difference, 2) . " ❌ NEEDS RECALCULATION\n";
            } else {
                echo "    Difference: ₱0.00 ✅ Correct\n";
            }
        }
        
        echo "\n";
        
        // Overall status
        if ($refund7070->refund_status === 'approved') {
            $hasProblems = ($refund7070->donation_status !== 'refunded' || !$refund7070->is_refunded);
            
            if ($hasProblems) {
                echo "  ❌ STATUS: NEEDS FIX\n";
                echo "     This approved refund is not properly reflected in the donation.\n";
                echo "     The campaign total may still include this refunded amount.\n\n";
            } else {
                echo "  ✅ STATUS: CORRECT\n";
                echo "     This refund is properly processed.\n\n";
            }
        } else {
            echo "  ℹ️  STATUS: Refund is still pending approval\n\n";
        }
        
    } else {
        echo "⚠️  Refund with amount ₱7,070.00 not found in database\n\n";
    }
    
    // Check for ₱375.00 refund (Green Earth Movement)
    echo "-------------------------------------------\n";
    echo "CHECKING: ₱375.00 REFUND (Green Earth Movement)\n";
    echo "-------------------------------------------\n";
    
    $refund375 = DB::table('refund_requests as rr')
        ->join('donations as d', 'rr.donation_id', '=', 'd.id')
        ->join('users as u', 'rr.user_id', '=', 'u.id')
        ->leftJoin('campaigns as c', 'd.campaign_id', '=', 'c.id')
        ->where('d.amount', 375.00)
        ->whereIn('rr.status', ['approved', 'denied', 'pending'])
        ->select(
            'rr.id as refund_id',
            'rr.status as refund_status',
            'd.id as donation_id',
            'd.status as donation_status',
            'd.is_refunded',
            'u.name as donor_name',
            'c.title as campaign_title'
        )
        ->first();
    
    if ($refund375) {
        echo "✓ Found refund in database:\n";
        echo "  Refund ID: {$refund375->refund_id}\n";
        echo "  Refund Status: {$refund375->refund_status}\n";
        echo "  Donor: {$refund375->donor_name}\n";
        echo "  Campaign: " . ($refund375->campaign_title ?? 'N/A') . "\n";
        echo "  Donation Status: {$refund375->donation_status}\n";
        
        if ($refund375->refund_status === 'denied') {
            echo "\n  ✓ STATUS: Denied (donation should remain as-is)\n\n";
        } elseif ($refund375->refund_status === 'approved') {
            $hasProblems = ($refund375->donation_status !== 'refunded' || !$refund375->is_refunded);
            
            if ($hasProblems) {
                echo "\n  ❌ STATUS: NEEDS FIX (approved but donation not updated)\n\n";
            } else {
                echo "\n  ✅ STATUS: CORRECT\n\n";
            }
        }
    } else {
        echo "⚠️  Refund with amount ₱375.00 not found in database\n\n";
    }
    
    // List ALL approved refunds for this charity
    echo "==============================================\n";
    echo "ALL APPROVED REFUNDS IN DATABASE\n";
    echo "==============================================\n\n";
    
    $allApproved = DB::table('refund_requests as rr')
        ->join('donations as d', 'rr.donation_id', '=', 'd.id')
        ->join('users as u', 'rr.user_id', '=', 'u.id')
        ->leftJoin('campaigns as c', 'd.campaign_id', '=', 'c.id')
        ->where('rr.status', 'approved')
        ->select(
            'rr.id as refund_id',
            'd.amount',
            'd.status as donation_status',
            'd.is_refunded',
            'u.name as donor_name',
            'c.title as campaign_title',
            'rr.reviewed_at'
        )
        ->orderBy('rr.reviewed_at', 'desc')
        ->get();
    
    if ($allApproved->isEmpty()) {
        echo "No approved refunds found in the database.\n\n";
    } else {
        echo "Found " . $allApproved->count() . " approved refund(s):\n\n";
        
        foreach ($allApproved as $refund) {
            $status = ($refund->donation_status === 'refunded' && $refund->is_refunded) ? '✅' : '❌';
            
            echo "{$status} Refund #{$refund->refund_id} - ₱" . number_format($refund->amount, 2) . "\n";
            echo "   Donor: {$refund->donor_name}\n";
            echo "   Campaign: " . ($refund->campaign_title ?? 'N/A') . "\n";
            echo "   Donation Status: {$refund->donation_status} | Is Refunded: " . ($refund->is_refunded ? 'Yes' : 'No') . "\n";
            echo "   Approved: {$refund->reviewed_at}\n\n";
        }
    }
    
    echo "==============================================\n";
    echo "RECOMMENDATION\n";
    echo "==============================================\n\n";
    
    $needsFix = DB::table('refund_requests as rr')
        ->join('donations as d', 'rr.donation_id', '=', 'd.id')
        ->where('rr.status', 'approved')
        ->where(function($query) {
            $query->where('d.status', '!=', 'refunded')
                  ->orWhere('d.is_refunded', false);
        })
        ->count();
    
    if ($needsFix > 0) {
        echo "❌ {$needsFix} approved refund(s) need to be fixed!\n\n";
        echo "These refunds are approved but donations are still marked as 'completed'.\n";
        echo "This means campaign totals have NOT been reduced.\n\n";
        echo "TO FIX, run:\n";
        echo "  php database/scripts/fix_refund_donations.php\n\n";
    } else {
        echo "✅ All approved refunds are correctly processed!\n\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERROR OCCURRED:\n";
    echo $e->getMessage() . "\n\n";
    exit(1);
}
