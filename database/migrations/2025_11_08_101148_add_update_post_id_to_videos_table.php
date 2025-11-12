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
        Schema::table('videos', function (Blueprint $table) {
            $table->foreignId('update_post_id')->nullable()->after('campaign_id')
                  ->constrained('updates')->onDelete('cascade');
            
            // Make campaign_id nullable since video can belong to either campaign or update
            $table->foreignId('campaign_id')->nullable()->change();
            
            // Add index for update videos
            $table->index(['update_post_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropForeign(['update_post_id']);
            $table->dropIndex(['update_post_id', 'status']);
            $table->dropColumn('update_post_id');
            
            // Revert campaign_id to non-nullable
            $table->foreignId('campaign_id')->nullable(false)->change();
        });
    }
};
