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
        Schema::table('email_verifications', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('email_verifications', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
            
            if (!Schema::hasColumn('email_verifications', 'code')) {
                $table->string('code', 10)->nullable()->after('token');
            }
            
            if (!Schema::hasColumn('email_verifications', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('code');
            }
            
            if (!Schema::hasColumn('email_verifications', 'attempts')) {
                $table->integer('attempts')->default(0)->after('expires_at');
            }
            
            if (!Schema::hasColumn('email_verifications', 'resend_count')) {
                $table->integer('resend_count')->default(0)->after('attempts');
            }
            
            if (!Schema::hasColumn('email_verifications', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
        
        // Add index for email + code
        try {
            Schema::table('email_verifications', function (Blueprint $table) {
                $table->index(['email', 'code'], 'email_verifications_email_code_index');
            });
        } catch (\Exception $e) {
            // Index might already exist, ignore
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_verifications', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'user_id',
                'code',
                'expires_at',
                'attempts',
                'resend_count',
                'updated_at'
            ]);
        });
    }
};
