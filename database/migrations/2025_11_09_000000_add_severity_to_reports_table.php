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
        Schema::table('reports', function (Blueprint $table) {
            // Add severity column if it doesn't exist
            if (!Schema::hasColumn('reports', 'severity')) {
                $table->enum('severity', ['pending', 'low', 'medium', 'high', 'critical'])
                    ->default('pending')
                    ->after('report_type')
                    ->comment('Admin determines severity during review');
            }
            
            // Add additional fields for improved report management
            if (!Schema::hasColumn('reports', 'target_type')) {
                $table->enum('target_type', ['user', 'charity', 'campaign', 'donation'])
                    ->nullable()
                    ->after('reported_entity_id');
            }
            
            if (!Schema::hasColumn('reports', 'target_id')) {
                $table->unsignedBigInteger('target_id')->nullable()->after('target_type');
            }
            
            if (!Schema::hasColumn('reports', 'report_type')) {
                $table->enum('report_type', [
                    'fraud',
                    'misleading_information',
                    'abuse',
                    'spam',
                    'fake_donation',
                    'misuse_of_funds',
                    'inappropriate_content',
                    'fake_charity',
                    'harassment',
                    'other'
                ])->nullable()->after('reported_entity_type');
            }
            
            if (!Schema::hasColumn('reports', 'details')) {
                $table->text('details')->nullable()->after('description');
            }
            
            if (!Schema::hasColumn('reports', 'penalty_days')) {
                $table->integer('penalty_days')->nullable()->after('action_taken');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn([
                'severity',
                'target_type',
                'target_id',
                'report_type',
                'details',
                'penalty_days'
            ]);
        });
    }
};
