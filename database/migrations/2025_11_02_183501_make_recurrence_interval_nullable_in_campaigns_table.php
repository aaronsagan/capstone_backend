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
            // Make recurrence_interval nullable to prevent constraint violations
            $table->integer('recurrence_interval')->nullable()->change();
            // Make auto_publish nullable as well for consistency
            $table->boolean('auto_publish')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Revert to non-nullable with defaults
            $table->integer('recurrence_interval')->default(1)->change();
            $table->boolean('auto_publish')->default(true)->change();
        });
    }
};
