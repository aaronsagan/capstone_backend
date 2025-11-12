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
        Schema::create('charity_officers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charity_id')->constrained('charities')->onDelete('cascade');
            $table->string('name');
            $table->string('position'); // e.g., President, Vice President, Treasurer, Secretary
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('profile_image_path')->nullable();
            $table->text('bio')->nullable();
            $table->integer('display_order')->default(0); // For ordering officers
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Index for faster queries
            $table->index(['charity_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charity_officers');
    }
};
