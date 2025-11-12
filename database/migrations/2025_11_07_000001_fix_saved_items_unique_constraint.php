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
        Schema::table('saved_items', function (Blueprint $table) {
            // Drop old unique constraint if it exists (from campaign_id days)
            try {
                $table->dropUnique(['user_id', 'campaign_id']);
            } catch (\Exception $e) {
                // Constraint might not exist, that's ok
            }
            
            // Add correct unique constraint for polymorphic relationship
            // This ensures a user can only save the same item once
            $table->unique(['user_id', 'savable_id', 'savable_type'], 'saved_items_user_savable_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saved_items', function (Blueprint $table) {
            $table->dropUnique('saved_items_user_savable_unique');
        });
    }
};
