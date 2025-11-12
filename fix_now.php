<?php

/**
 * Direct Fix - No Confirmation Needed
 * Fixes Aeron's approved refund immediately
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n==============================================\n";
echo "   FIXING APPROVED REFUND NOW\n";
echo "==============================================\n\n";

try {
    DB::beginTransaction();
    
    // Find and fix the approved refund
    $refund = DB::selectOne("
        SELECT 
            rr.id as refund_id,
            rr.reviewed_at,
            d.id as donation_id,
            d.campaign_id,
            d.charity_id,
            d.amount,
            u.name as donor
        FROM refund_requests rr
        INNER JOIN donations d ON rr.donation_id = d.id
        INNER JOIN users u ON rr.user_id = u.id
        WHERE rr.status = 'approved'
          AND d.amount = 2070.00
    ");
    
    if (!$refund) {
        echo "No refund found to fix.\n\n";
        DB::rollBack();
        exit(0);
    }
    
    echo "Found: Refund #{$refund->refund_id}\n";
    echo "Donor: {$refund->donor}\n";
    echo "Amount: ₱" . number_format($refund->amount, 2) . "\n\n";
    
    echo "Updating donation status...\n";
    
    // Force update with raw SQL
    $updated = DB::update("
        UPDATE donations 
        SET 
            status = 'refunded',
            is_refunded = 1,
            refunded_at = ?
        WHERE id = ?
    ", [$refund->reviewed_at ?? now(), $refund->donation_id]);
    
    if ($updated === 0) {
        throw new \Exception("Failed to update donation");
    }
    
    echo "✅ Donation status updated to 'refunded'\n\n";
    
    // Recalculate campaign total
    if ($refund->campaign_id) {
        echo "Recalculating campaign totals...\n";
        
        $totals = DB::selectOne("
            SELECT 
                COALESCE(SUM(amount), 0) as total,
                COUNT(DISTINCT donor_id) as donors
            FROM donations
            WHERE campaign_id = ?
              AND status = 'completed'
              AND is_refunded = 0
        ", [$refund->campaign_id]);
        
        DB::update("
            UPDATE campaigns
            SET 
                total_donations_received = ?,
                donors_count = ?
            WHERE id = ?
        ", [$totals->total, $totals->donors, $refund->campaign_id]);
        
        echo "✅ Campaign total: ₱" . number_format($totals->total, 2) . "\n\n";
    }
    
    // Recalculate charity total
    if ($refund->charity_id) {
        echo "Recalculating charity totals...\n";
        
        $totals = DB::selectOne("
            SELECT 
                COALESCE(SUM(amount), 0) as total,
                COUNT(DISTINCT donor_id) as donors
            FROM donations
            WHERE charity_id = ?
              AND status = 'completed'
              AND is_refunded = 0
        ", [$refund->charity_id]);
        
        DB::update("
            UPDATE charities
            SET 
                total_donations_received = ?,
                donors_count = ?
            WHERE id = ?
        ", [$totals->total, $totals->donors, $refund->charity_id]);
        
        echo "✅ Charity total: ₱" . number_format($totals->total, 2) . "\n\n";
    }
    
    DB::commit();
    
    echo "==============================================\n";
    echo "✅ FIX COMPLETE!\n";
    echo "==============================================\n\n";
    
    echo "What changed:\n";
    echo "  ✓ Donation status: completed → refunded\n";
    echo "  ✓ is_refunded flag: false → true\n";
    echo "  ✓ Campaign total reduced by ₱" . number_format($refund->amount, 2) . "\n";
    echo "  ✓ Charity total reduced\n\n";
    
    echo "Refresh your browser (Ctrl+F5) to see changes!\n\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: {$e->getMessage()}\n\n";
    exit(1);
}
