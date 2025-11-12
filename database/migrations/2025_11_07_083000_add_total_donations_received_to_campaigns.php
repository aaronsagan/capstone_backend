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
        Schema::table('campaigns', function (Blueprint $table) {
            // Add total_donations_received column to store actual donations received
            $table->decimal('total_donations_received', 12, 2)->default(0)->after('target_amount');
            
            // Add donors_count for performance (avoid counting repeatedly)
            $table->integer('donors_count')->default(0)->after('total_donations_received');
            
            // Add index for better query performance
            $table->index(['total_donations_received', 'status']);
        });

        // Backfill existing data - calculate totals from completed donations
        DB::statement("
            UPDATE campaigns c
            LEFT JOIN (
                SELECT 
                    campaign_id,
                    SUM(amount) as total_received,
                    COUNT(DISTINCT donor_id) as unique_donors
                FROM donations
                WHERE status = 'completed' AND campaign_id IS NOT NULL
                GROUP BY campaign_id
            ) d ON c.id = d.campaign_id
            SET 
                c.total_donations_received = COALESCE(d.total_received, 0),
                c.donors_count = COALESCE(d.unique_donors, 0)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex(['total_donations_received', 'status']);
            $table->dropColumn(['total_donations_received', 'donors_count']);
        });
    }
};
