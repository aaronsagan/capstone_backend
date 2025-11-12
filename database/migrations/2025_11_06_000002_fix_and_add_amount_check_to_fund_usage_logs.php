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
        // First, check if there are any fund usage logs with amount < 1
        $invalidLogs = DB::table('fund_usage_logs')
            ->where('amount', '<', 1)
            ->get();

        if ($invalidLogs->count() > 0) {
            // Log the invalid entries for reference
            \Log::warning('Found ' . $invalidLogs->count() . ' fund usage logs with amount < 1. These will be updated to 1.');
            
            // Update all invalid amounts to 1 (minimum valid amount)
            DB::table('fund_usage_logs')
                ->where('amount', '<', 1)
                ->update(['amount' => 1]);
            
            echo "Updated {$invalidLogs->count()} fund usage logs with invalid amounts to ₱1.\n";
        }

        // Now add the CHECK constraint
        // Note: MySQL 8.0.16+ supports CHECK constraints
        DB::statement('ALTER TABLE fund_usage_logs ADD CONSTRAINT fund_usage_logs_amount_min CHECK (amount >= 1)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the CHECK constraint
        DB::statement('ALTER TABLE fund_usage_logs DROP CHECK fund_usage_logs_amount_min');
        
        // Note: We don't revert the amount updates as those were invalid data
    }
};
