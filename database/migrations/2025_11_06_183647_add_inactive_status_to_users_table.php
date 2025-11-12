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
        // Modify the status enum to include 'inactive'
        DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'suspended', 'inactive') DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        // First, update any 'inactive' users to 'suspended'
        DB::statement("UPDATE users SET status = 'suspended' WHERE status = 'inactive'");
        // Then modify the enum
        DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'suspended') DEFAULT 'active'");
    }
};
