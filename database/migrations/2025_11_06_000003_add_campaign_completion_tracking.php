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
            $table->boolean('requires_completion_report')->default(true)->after('status');
            $table->boolean('completion_report_submitted')->default(false)->after('requires_completion_report');
            $table->timestamp('completion_report_submitted_at')->nullable()->after('completion_report_submitted');
            $table->boolean('has_fund_usage_logs')->default(false)->after('completion_report_submitted_at');
            $table->timestamp('ended_at')->nullable()->after('end_date');
        });

        Schema::table('campaign_updates', function (Blueprint $table) {
            $table->boolean('is_completion_report')->default(false)->after('is_milestone');
            $table->json('fund_summary')->nullable()->after('is_completion_report');
        });

        Schema::table('fund_usage_logs', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('attachment_path');
            $table->string('receipt_number')->nullable()->after('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'requires_completion_report',
                'completion_report_submitted',
                'completion_report_submitted_at',
                'has_fund_usage_logs',
                'ended_at'
            ]);
        });

        Schema::table('campaign_updates', function (Blueprint $table) {
            $table->dropColumn(['is_completion_report', 'fund_summary']);
        });

        Schema::table('fund_usage_logs', function (Blueprint $table) {
            $table->dropColumn(['is_verified', 'receipt_number']);
        });
    }
};
