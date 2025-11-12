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
        Schema::table('donations', function (Blueprint $table) {
            $table->boolean('is_refunded')->default(false)->after('status');
            $table->timestamp('refunded_at')->nullable()->after('is_refunded');
            
            // Add index for refund queries
            $table->index('is_refunded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropIndex(['is_refunded']);
            $table->dropColumn(['is_refunded', 'refunded_at']);
        });
    }
};
