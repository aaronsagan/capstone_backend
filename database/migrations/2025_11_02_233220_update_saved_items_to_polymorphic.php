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
        if (Schema::hasTable('saved_items')) {
            // Add polymorphic columns if missing
            if (!Schema::hasColumn('saved_items', 'savable_id') || !Schema::hasColumn('saved_items', 'savable_type')) {
                Schema::table('saved_items', function (Blueprint $table) {
                    $table->morphs('savable');
                });
            }

            // Make campaign_id nullable if it exists
            if (Schema::hasColumn('saved_items', 'campaign_id')) {
                Schema::table('saved_items', function (Blueprint $table) {
                    $table->unsignedBigInteger('campaign_id')->nullable()->change();
                });
            }
        }

        // Migrate existing data if columns are present
        if (Schema::hasTable('saved_items') &&
            Schema::hasColumn('saved_items', 'savable_id') &&
            Schema::hasColumn('saved_items', 'savable_type') &&
            Schema::hasColumn('saved_items', 'campaign_id')) {
            DB::table('saved_items')->whereNotNull('campaign_id')->update([
                'savable_id' => DB::raw('campaign_id'),
                'savable_type' => 'App\\Models\\Campaign'
            ]);
        }

        // Now drop the old campaign_id column if it exists
        if (Schema::hasTable('saved_items') && Schema::hasColumn('saved_items', 'campaign_id')) {
            try {
                Schema::table('saved_items', function (Blueprint $table) {
                    // Attempt to drop foreign key if present
                    try { $table->dropForeign(['campaign_id']); } catch (\Throwable $e) { /* ignore */ }
                    $table->dropColumn('campaign_id');
                });
            } catch (\Throwable $e) {
                // ignore if already dropped
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('saved_items')) {
            Schema::table('saved_items', function (Blueprint $table) {
                // Add back campaign_id if missing
                if (!Schema::hasColumn('saved_items', 'campaign_id')) {
                    $table->unsignedBigInteger('campaign_id')->nullable();
                    $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
                }
            });
        }

        // Migrate data back if columns exist
        if (Schema::hasTable('saved_items') &&
            Schema::hasColumn('saved_items', 'savable_id') &&
            Schema::hasColumn('saved_items', 'savable_type') &&
            Schema::hasColumn('saved_items', 'campaign_id')) {
            DB::table('saved_items')
                ->where('savable_type', 'App\\Models\\Campaign')
                ->update(['campaign_id' => DB::raw('savable_id')]);
        }

        // Drop polymorphic columns if present
        if (Schema::hasTable('saved_items') &&
            (Schema::hasColumn('saved_items', 'savable_id') || Schema::hasColumn('saved_items', 'savable_type'))) {
            Schema::table('saved_items', function (Blueprint $table) {
                $table->dropMorphs('savable');
            });
        }
    }
};
