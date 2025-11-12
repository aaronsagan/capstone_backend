<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('charities', function (Blueprint $table) {
            // Add total_donations_received column to track all donations to this charity
            $table->decimal('total_donations_received', 12, 2)->default(0)->after('contact_phone');
            
            // Add donors_count for performance (avoid counting repeatedly)
            $table->integer('donors_count')->default(0)->after('total_donations_received');
            
            // Add campaigns_count to track active campaigns
            $table->integer('campaigns_count')->default(0)->after('donors_count');
            
            // Add index for better query performance
            $table->index(['total_donations_received', 'verification_status']);
        });

        // Backfill existing data - calculate totals from completed donations
        DB::statement("
            UPDATE charities c
            LEFT JOIN (
                SELECT 
                    charity_id,
                    SUM(amount) as total_received,
                    COUNT(DISTINCT donor_id) as unique_donors
                FROM donations
                WHERE status = 'completed'
                GROUP BY charity_id
            ) d ON c.id = d.charity_id
            SET 
                c.total_donations_received = COALESCE(d.total_received, 0),
                c.donors_count = COALESCE(d.unique_donors, 0)
        ");

        // Backfill campaigns count
        DB::statement("
            UPDATE charities c
            LEFT JOIN (
                SELECT 
                    charity_id,
                    COUNT(*) as campaign_count
                FROM campaigns
                WHERE status IN ('published', 'closed')
                GROUP BY charity_id
            ) camp ON c.id = camp.charity_id
            SET 
                c.campaigns_count = COALESCE(camp.campaign_count, 0)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charities', function (Blueprint $table) {
            $table->dropIndex(['total_donations_received', 'verification_status']);
            $table->dropColumn(['total_donations_received', 'donors_count', 'campaigns_count']);
        });
    }
};
