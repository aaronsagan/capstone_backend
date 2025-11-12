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
        Schema::table('charity_documents', function (Blueprint $table) {
            $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
            $table->timestamp('can_resubmit_at')->nullable()->after('rejected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charity_documents', function (Blueprint $table) {
            $table->dropColumn(['rejected_at', 'can_resubmit_at']);
        });
    }
};
