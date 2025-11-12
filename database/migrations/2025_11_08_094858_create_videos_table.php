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
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('original_filename');
            $table->string('filename');
            $table->string('path');
            $table->string('mime');
            $table->bigInteger('size'); // bytes
            $table->integer('duration')->nullable(); // seconds
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->enum('status', ['pending', 'processing', 'ready', 'rejected'])->default('pending');
            $table->timestamps();
            
            $table->index(['campaign_id', 'status']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
