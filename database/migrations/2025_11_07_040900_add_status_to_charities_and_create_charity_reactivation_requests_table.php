<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add status to charities if not exists
        Schema::table('charities', function (Blueprint $table) {
            if (!Schema::hasColumn('charities', 'status')) {
                $table->enum('status', ['active','inactive','suspended'])->default('active')->after('verification_status');
            }
        });

        // Charity reactivation requests
        Schema::create('charity_reactivation_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('charity_id');
            $table->string('email')->nullable();
            $table->enum('status', ['pending','approved','rejected'])->default('pending');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->foreign('charity_id')->references('id')->on('charities')->onDelete('cascade');
            $table->index(['charity_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charity_reactivation_requests');
        Schema::table('charities', function (Blueprint $table) {
            if (Schema::hasColumn('charities', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
