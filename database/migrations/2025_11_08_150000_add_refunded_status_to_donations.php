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
        // For MySQL/MariaDB, we need to use raw SQL to modify the enum
        DB::statement("ALTER TABLE donations MODIFY COLUMN status ENUM('pending', 'completed', 'rejected', 'refunded') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert any refunded donations back to completed before removing the enum value
        DB::table('donations')
            ->where('status', 'refunded')
            ->update(['status' => 'completed']);
        
        // Remove 'refunded' from enum
        DB::statement("ALTER TABLE donations MODIFY COLUMN status ENUM('pending', 'completed', 'rejected') NOT NULL DEFAULT 'pending'");
    }
};
