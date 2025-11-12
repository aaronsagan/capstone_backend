<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add charity_id as nullable first (only if not exists)
        if (!Schema::hasColumn('refund_requests', 'charity_id')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('charity_id')->nullable()->after('user_id');
            });
            
            // Populate charity_id from donations table for existing records
            DB::statement('
                UPDATE refund_requests r
                INNER JOIN donations d ON r.donation_id = d.id
                SET r.charity_id = d.charity_id
            ');
            
            // Now make charity_id required and add foreign key
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('charity_id')->nullable(false)->change();
                $table->foreign('charity_id')->references('id')->on('charities')->onDelete('cascade');
                $table->index('charity_id');
            });
        }
        
        // Add other new columns
        Schema::table('refund_requests', function (Blueprint $table) {
            // Add proof_url
            if (!Schema::hasColumn('refund_requests', 'proof_url')) {
                $table->string('proof_url')->nullable()->after('reason');
            }
            
            // Add charity_response
            if (!Schema::hasColumn('refund_requests', 'charity_response')) {
                $table->text('charity_response')->nullable()->after('admin_notes');
            }
        });
        
        // Rename admin_notes to charity_notes
        if (Schema::hasColumn('refund_requests', 'admin_notes')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->renameColumn('admin_notes', 'charity_notes');
            });
        }
        
        // Update status enum
        DB::statement("ALTER TABLE refund_requests MODIFY status ENUM('pending', 'approved', 'denied', 'cancelled') DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropForeign(['charity_id']);
            $table->dropIndex(['charity_id']);
            $table->dropColumn(['charity_id', 'proof_url', 'charity_response']);
        });
        
        if (Schema::hasColumn('refund_requests', 'charity_notes')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->renameColumn('charity_notes', 'admin_notes');
            });
        }
        
        DB::statement("ALTER TABLE refund_requests MODIFY status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending'");
    }
};
