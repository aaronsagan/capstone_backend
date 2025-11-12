<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\DonorMilestone;

class DonorMilestoneSeeder extends Seeder
{
    /**
     * Seed donor milestones for all donors.
     * Creates default milestone definitions (not achieved).
     */
    public function run(): void
    {
        $milestones = [
            [
                'key' => 'first_donation',
                'title' => 'First Donation',
                'description' => 'Made your first charitable donation',
                'icon' => 'Heart',
            ],
            [
                'key' => 'total_1000',
                'title' => 'Generous Start',
                'description' => 'Donated a total of ₱1,000 or more',
                'icon' => 'TrendingUp',
            ],
            [
                'key' => 'total_10000',
                'title' => 'Generous Giver',
                'description' => 'Donated a total of ₱10,000 or more',
                'icon' => 'Award',
            ],
            [
                'key' => 'total_50000',
                'title' => 'Super Donor',
                'description' => 'Donated a total of ₱50,000 or more',
                'icon' => 'Trophy',
            ],
            [
                'key' => 'total_100000',
                'title' => 'Platinum Supporter',
                'description' => 'Donated a total of ₱100,000 or more',
                'icon' => 'Crown',
            ],
            [
                'key' => 'supported_5_campaigns',
                'title' => 'Community Supporter',
                'description' => 'Supported 5 different campaigns',
                'icon' => 'Users',
            ],
            [
                'key' => 'supported_10_campaigns',
                'title' => 'Campaign Champion',
                'description' => 'Supported 10 different campaigns',
                'icon' => 'Flag',
            ],
            [
                'key' => 'supported_25_campaigns',
                'title' => 'Widespread Impact',
                'description' => 'Supported 25 different campaigns',
                'icon' => 'Globe',
            ],
            [
                'key' => 'donations_10',
                'title' => 'Active Supporter',
                'description' => 'Made 10 or more donations',
                'icon' => 'Zap',
            ],
            [
                'key' => 'donations_25',
                'title' => 'Dedicated Donor',
                'description' => 'Made 25 or more donations',
                'icon' => 'Star',
            ],
            [
                'key' => 'donations_50',
                'title' => 'Philanthropist',
                'description' => 'Made 50 or more donations',
                'icon' => 'Sparkles',
            ],
            [
                'key' => 'member_1_year',
                'title' => 'One Year Anniversary',
                'description' => 'Been a member for 1 year',
                'icon' => 'Calendar',
            ],
            [
                'key' => 'verified_donor',
                'title' => 'Verified Donor',
                'description' => 'Verified your account and made at least one donation',
                'icon' => 'ShieldCheck',
            ],
        ];

        // Get all donors
        $donors = User::where('role', 'donor')
            ->whereNotNull('email_verified_at')
            ->get();

        foreach ($donors as $donor) {
            foreach ($milestones as $milestoneData) {
                // Check if milestone already exists for this donor
                $exists = DonorMilestone::where('donor_id', $donor->id)
                    ->where('key', $milestoneData['key'])
                    ->exists();

                if (!$exists) {
                    DonorMilestone::create([
                        'donor_id' => $donor->id,
                        'key' => $milestoneData['key'],
                        'title' => $milestoneData['title'],
                        'description' => $milestoneData['description'],
                        'icon' => $milestoneData['icon'],
                        'achieved_at' => null, // Will be set by refresh command
                        'meta' => null,
                    ]);
                }
            }
        }

        $this->command->info('Donor milestones seeded successfully!');
        $this->command->info("Seeded milestones for {$donors->count()} donors.");
        $this->command->info('Run "php artisan donor:refresh-milestones" to evaluate achievements.');
    }
}
