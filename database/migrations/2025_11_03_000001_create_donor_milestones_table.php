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
        if (!Schema::hasTable('donor_milestones')) {
            Schema::create('donor_milestones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('donor_id')->constrained('users')->onDelete('cascade');
                $table->string('key')->comment('Unique machine key, e.g., first_donation');
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('icon')->nullable()->comment('Icon name from lucide-react');
                $table->timestamp('achieved_at')->nullable()->comment('When milestone was achieved');
                $table->json('meta')->nullable()->comment('Progress and additional data');
                $table->timestamps();
                
                // Indexes for performance
                $table->index('donor_id');
                $table->unique(['donor_id', 'key']);
                $table->index('achieved_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donor_milestones');
    }
};
