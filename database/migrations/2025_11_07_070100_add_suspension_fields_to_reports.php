<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Add new suspension system fields
            $table->string('target_type')->nullable()->after('reported_entity_id');
            $table->unsignedBigInteger('target_id')->nullable()->after('target_type');
            $table->string('report_type')->nullable()->after('reason');
            $table->enum('severity', ['low', 'medium', 'high'])->default('medium')->after('report_type');
            $table->text('details')->nullable()->after('description');
            $table->integer('penalty_days')->nullable()->after('admin_notes');
            
            // Add index for new fields
            $table->index(['target_type', 'target_id']);
        });
        
        // Update existing status enum to include new statuses
        DB::statement("ALTER TABLE reports MODIFY COLUMN status ENUM('pending', 'under_review', 'resolved', 'dismissed', 'approved', 'rejected') DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['target_type', 'target_id', 'report_type', 'severity', 'details', 'penalty_days']);
            $table->dropIndex(['target_type', 'target_id']);
        });
        
        // Revert status enum
        DB::statement("ALTER TABLE reports MODIFY COLUMN status ENUM('pending', 'under_review', 'resolved', 'dismissed') DEFAULT 'pending'");
    }
};
