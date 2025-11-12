<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class ClearDataSeeder extends Seeder
{
    /**
     * Clear all seeded data except admin@example.com account
     */
    public function run(): void
    {
        $this->command->info('Starting data cleanup...');

        // Get admin user ID to preserve
        $adminUser = User::where('email', 'admin@example.com')->first();
        
        if (!$adminUser) {
            $this->command->warn('Admin user not found!');
            return;
        }

        $adminId = $adminUser->id;
        $this->command->info("Preserving admin user (ID: {$adminId})");

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Delete all data from tables (except admin user)
        // Clear all related data tables
        $this->command->info('Clearing campaign updates...');
        DB::table('campaign_updates')->truncate();

        $this->command->info('Clearing update comments...');
        DB::table('update_comments')->truncate();

        $this->command->info('Clearing comment likes...');
        DB::table('comment_likes')->truncate();

        $this->command->info('Clearing update shares...');
        DB::table('update_shares')->truncate();

        $this->command->info('Clearing campaign comments...');
        DB::table('campaign_comments')->truncate();

        $this->command->info('Clearing fund usage logs...');
        DB::table('fund_usage_logs')->truncate();

        $this->command->info('Clearing donations...');
        DB::table('donations')->truncate();

        $this->command->info('Clearing campaign donation channels...');
        DB::table('campaign_donation_channel')->truncate();

        $this->command->info('Clearing campaigns...');
        DB::table('campaigns')->truncate();

        $this->command->info('Clearing donation channels...');
        DB::table('donation_channels')->truncate();

        $this->command->info('Clearing charity documents...');
        DB::table('charity_documents')->truncate();

        $this->command->info('Clearing charity follows...');
        DB::table('charity_follows')->truncate();

        $this->command->info('Clearing charities...');
        DB::table('charities')->truncate();

        $this->command->info('Clearing categories...');
        DB::table('categories')->truncate();

        $this->command->info('Clearing volunteers...');
        DB::table('volunteers')->truncate();

        $this->command->info('Clearing donor profiles...');
        DB::table('donor_profiles')->truncate();

        $this->command->info('Clearing donor verifications...');
        DB::table('donor_verifications')->truncate();

        $this->command->info('Clearing donor registration drafts...');
        DB::table('donor_registration_drafts')->truncate();

        $this->command->info('Clearing notifications...');
        DB::table('notifications')->truncate();

        $this->command->info('Clearing activity logs...');
        DB::table('activity_logs')->truncate();

        $this->command->info('Clearing reports...');
        DB::table('reports')->truncate();

        $this->command->info('Clearing admin action logs...');
        DB::table('admin_action_logs')->truncate();

        $this->command->info('Clearing users (except admin)...');
        User::where('id', '!=', $adminId)->delete();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('✓ Data cleanup complete! Admin account preserved.');
    }
}
