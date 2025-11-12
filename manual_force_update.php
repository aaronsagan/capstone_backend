<?php

/**
 * Manual Force Update - Bypass Everything
 * 
 * This script uses raw SQL to directly update the database,
 * bypassing Eloquent models, events, and any other Laravel features
 * that might be preventing the update.
 * 
 * Run: php manual_force_update.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "==============================================\n";
echo "   MANUAL FORCE UPDATE (RAW SQL)\n";
echo "==============================================\n\n";

echo "⚠️  WARNING: This bypasses all Laravel safeguards!\n";
echo "It will use raw SQL to force the update.\n\n";

echo "Press any key to continue or Ctrl+C to cancel...\n";
if (function_exists('readline')) {
    readline();
} else {
    fgets(STDIN);
}

try {
    // Start transaction
    DB::beginTransaction();
    
    echo "\nStep 1: Finding approved refunds...\n";
    
    // Find all approved refunds with incorrect donation status
    $refunds = DB::select("
        SELECT 
            rr.id as refund_id,
            rr.reviewed_at,
            d.id as donation_id,
            d.status as donation_status,
            d.is_refunded,
            d.amount,
            d.campaign_id,
            d.charity_id,
            u.name as donor_name,
            u.email as donor_email,
            c.title as campaign_title
        FROM refund_requests rr
        INNER JOIN donations d ON rr.donation_id = d.id
        INNER JOIN users u ON rr.user_id = u.id
        LEFT JOIN campaigns c ON d.campaign_id = c.id
        WHERE rr.status = 'approved'
          AND (d.status != 'refunded' OR d.is_refunded = 0)
    ");
    
    if (empty($refunds)) {
        echo "✓ No refunds need updating!\n";
        echo "All approved refunds are already correctly marked.\n\n";
        DB::rollBack();
        exit(0);
    }
    
    echo "Found " . count($refunds) . " refund(s) that need updating:\n\n";
    
    foreach ($refunds as $refund) {
        echo "  - Refund #{$refund->refund_id}\n";
        echo "    Donor: {$refund->donor_name}\n";
        echo "    Amount: ₱" . number_format($refund->amount, 2) . "\n";
        echo "    Campaign: " . ($refund->campaign_title ?? 'N/A') . "\n";
        echo "    Current status: {$refund->donation_status}\n";
        echo "\n";
    }
    
    echo "Step 2: Updating donations with RAW SQL...\n\n";
    
    $updatedCount = 0;
    $affectedCampaigns = [];
    $affectedCharities = [];
    
    foreach ($refunds as $refund) {
        $reviewedAt = $refund->reviewed_at ?? date('Y-m-d H:i:s');
        
        // Force update with raw SQL
        $affected = DB::update("
            UPDATE donations 
            SET 
                status = 'refunded',
                is_refunded = 1,
                refunded_at = ?,
                updated_at = NOW()
            WHERE id = ?
        ", [$reviewedAt, $refund->donation_id]);
        
        if ($affected > 0) {
            echo "  ✓ Updated donation #{$refund->donation_id} (₱" . number_format($refund->amount, 2) . ")\n";
            $updatedCount++;
            
            // Track campaigns and charities that need recalculation
            if ($refund->campaign_id) {
                $affectedCampaigns[$refund->campaign_id] = true;
            }
            if ($refund->charity_id) {
                $affectedCharities[$refund->charity_id] = true;
            }
        } else {
            echo "  ⚠️  Failed to update donation #{$refund->donation_id}\n";
        }
    }
    
    echo "\n";
    echo "Step 3: Recalculating campaign totals...\n\n";
    
    foreach (array_keys($affectedCampaigns) as $campaignId) {
        // Calculate correct total using raw SQL
        $result = DB::select("
            SELECT 
                COALESCE(SUM(amount), 0) as total,
                COUNT(DISTINCT donor_id) as donors
            FROM donations
            WHERE campaign_id = ?
              AND status = 'completed'
              AND is_refunded = 0
        ", [$campaignId])[0] ?? null;
        
        if ($result) {
            // Update campaign with raw SQL
            DB::update("
                UPDATE campaigns
                SET 
                    total_donations_received = ?,
                    donors_count = ?
                WHERE id = ?
            ", [$result->total, $result->donors, $campaignId]);
            
            $campaign = DB::selectOne("SELECT title FROM campaigns WHERE id = ?", [$campaignId]);
            echo "  ✓ Campaign '{$campaign->title}' updated: ₱" . number_format($result->total, 2) . " ({$result->donors} donors)\n";
        }
    }
    
    echo "\n";
    echo "Step 4: Recalculating charity totals...\n\n";
    
    foreach (array_keys($affectedCharities) as $charityId) {
        // Calculate correct total using raw SQL
        $result = DB::select("
            SELECT 
                COALESCE(SUM(amount), 0) as total,
                COUNT(DISTINCT donor_id) as donors
            FROM donations
            WHERE charity_id = ?
              AND status = 'completed'
              AND is_refunded = 0
        ", [$charityId])[0] ?? null;
        
        if ($result) {
            // Update charity with raw SQL
            DB::update("
                UPDATE charities
                SET 
                    total_donations_received = ?,
                    donors_count = ?
                WHERE id = ?
            ", [$result->total, $result->donors, $charityId]);
            
            $charity = DB::selectOne("SELECT name FROM charities WHERE id = ?", [$charityId]);
            echo "  ✓ Charity '{$charity->name}' updated: ₱" . number_format($result->total, 2) . " ({$result->donors} donors)\n";
        }
    }
    
    echo "\n";
    echo "Step 5: Verifying updates...\n\n";
    
    // Verify all refunds are now correct
    $stillIncorrect = DB::select("
        SELECT COUNT(*) as count
        FROM refund_requests rr
        INNER JOIN donations d ON rr.donation_id = d.id
        WHERE rr.status = 'approved'
          AND (d.status != 'refunded' OR d.is_refunded = 0)
    ")[0]->count ?? 0;
    
    if ($stillIncorrect == 0) {
        echo "✅ ALL REFUNDS NOW CORRECTLY MARKED!\n";
        echo "\n";
        
        // Commit the changes
        DB::commit();
        
        echo "==============================================\n";
        echo "✅ FORCE UPDATE SUCCESSFUL\n";
        echo "==============================================\n";
        echo "Summary:\n";
        echo "  - Donations updated: {$updatedCount}\n";
        echo "  - Campaigns recalculated: " . count($affectedCampaigns) . "\n";
        echo "  - Charities recalculated: " . count($affectedCharities) . "\n\n";
        echo "Changes have been COMMITTED to the database.\n";
        echo "Refresh your browser to see the updates.\n\n";
    } else {
        echo "❌ {$stillIncorrect} refund(s) still incorrect after update!\n";
        echo "Rolling back changes...\n\n";
        DB::rollBack();
        
        echo "The update failed. Possible causes:\n";
        echo "  - Database constraints preventing the update\n";
        echo "  - Triggers reverting the changes\n";
        echo "  - Incorrect ENUM values in status column\n\n";
        
        exit(1);
    }
    
} catch (\Exception $e) {
    DB::rollBack();
    
    echo "\n❌ ERROR OCCURRED:\n";
    echo $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    
    echo "The update failed. This might be because:\n";
    echo "  1. The 'refunded' status is not in the ENUM - run migrations\n";
    echo "  2. Database permissions issue\n";
    echo "  3. Database constraints or triggers preventing update\n\n";
    
    exit(1);
}
