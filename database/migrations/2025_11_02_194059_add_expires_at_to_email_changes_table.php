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
        if (Schema::hasTable('email_changes')) {
            if (!Schema::hasColumn('email_changes', 'expires_at')) {
                Schema::table('email_changes', function (Blueprint $table) {
                    $table->timestamp('expires_at')->after('token');
                });
            }
            if (!Schema::hasColumn('email_changes', 'updated_at')) {
                Schema::table('email_changes', function (Blueprint $table) {
                    $table->timestamp('updated_at')->nullable()->after('expires_at');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('email_changes')) {
            if (Schema::hasColumn('email_changes', 'expires_at') || Schema::hasColumn('email_changes', 'updated_at')) {
                Schema::table('email_changes', function (Blueprint $table) {
                    if (Schema::hasColumn('email_changes', 'expires_at')) {
                        $table->dropColumn('expires_at');
                    }
                    if (Schema::hasColumn('email_changes', 'updated_at')) {
                        $table->dropColumn('updated_at');
                    }
                });
            }
        }
    }
};
