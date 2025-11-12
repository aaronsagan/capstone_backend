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
            // Recurring campaign configuration fields
            $table->boolean('is_recurring')->default(false)->after('donation_type');
            $table->enum('recurrence_type', ['weekly', 'monthly', 'quarterly', 'yearly'])->nullable()->after('is_recurring');
            $table->integer('recurrence_interval')->nullable()->after('recurrence_type')->comment('Number of units between repeats');
            $table->date('recurrence_start_date')->nullable()->after('recurrence_interval');
            $table->date('recurrence_end_date')->nullable()->after('recurrence_start_date');
            $table->date('next_occurrence_date')->nullable()->after('recurrence_end_date');
            $table->boolean('auto_publish')->default(true)->after('next_occurrence_date')->comment('Auto-publish new occurrences');
            $table->foreignId('parent_campaign_id')->nullable()->after('auto_publish')->constrained('campaigns')->onDelete('cascade')->comment('Reference to original recurring campaign');
            $table->integer('occurrence_number')->nullable()->after('parent_campaign_id')->comment('Which occurrence this is (1st, 2nd, etc.)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['parent_campaign_id']);
            $table->dropColumn([
                'is_recurring',
                'recurrence_type',
                'recurrence_interval',
                'recurrence_start_date',
                'recurrence_end_date',
                'next_occurrence_date',
                'auto_publish',
                'parent_campaign_id',
                'occurrence_number'
            ]);
        });
    }
};
