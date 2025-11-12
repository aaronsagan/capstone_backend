<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Add fields for volunteer-based campaigns
            $table->boolean('is_volunteer_based')->default(false)->after('campaign_type');
            $table->boolean('requires_target_amount')->default(true)->after('is_volunteer_based');
            $table->integer('volunteers_needed')->nullable()->after('requires_target_amount');
            $table->text('volunteer_description')->nullable()->after('volunteers_needed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['is_volunteer_based', 'requires_target_amount', 'volunteers_needed', 'volunteer_description']);
        });
    }
};
