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
        Schema::create('recurring_donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->foreignId('charity_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('interval', ['weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->enum('status', ['active', 'paused', 'cancelled'])->default('active');
            $table->timestamp('next_charge_at')->nullable();
            $table->timestamp('last_charged_at')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->integer('total_donations')->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('status');
            $table->index('next_charge_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_donations');
    }
};
