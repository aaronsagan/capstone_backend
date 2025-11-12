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
        // Add check constraint to donations table - amount must be at least 1 peso
        DB::statement('ALTER TABLE donations ADD CONSTRAINT check_donation_amount_minimum CHECK (amount >= 1.00)');
        
        // Add check constraint to fund_usage_logs table - amount must be at least 1 peso
        DB::statement('ALTER TABLE fund_usage_logs ADD CONSTRAINT check_fund_usage_amount_minimum CHECK (amount >= 1.00)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove check constraints
        DB::statement('ALTER TABLE donations DROP CONSTRAINT IF EXISTS check_donation_amount_minimum');
        DB::statement('ALTER TABLE fund_usage_logs DROP CONSTRAINT IF EXISTS check_fund_usage_amount_minimum');
    }
};
