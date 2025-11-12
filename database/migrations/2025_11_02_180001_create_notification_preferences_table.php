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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('category'); // donations, campaigns, charities, support, security, marketing
            $table->boolean('email')->default(true);
            $table->boolean('push')->default(false);
            $table->boolean('sms')->default(false);
            $table->string('frequency')->default('instant'); // instant, daily, weekly, monthly
            $table->timestamps();
            
            $table->unique(['user_id', 'category']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
