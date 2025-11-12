<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Core data
            CategorySeeder::class,
            UsersSeeder::class,
            DemoDataSeeder::class,
            
            // Test data with comprehensive scenarios
            TestDataSeeder::class,
            
            // Content and activity data
            CharityPostSeeder::class,
            CampaignUpdateSeeder::class,
            
            // Analytics and demo data
            AnalyticsDemoSeeder::class,
            
            // Milestones and logs
            DonorMilestoneSeeder::class,
            ActivityLogSeeder::class,
            FundUsageSeeder::class,
        ]);
    }
}
