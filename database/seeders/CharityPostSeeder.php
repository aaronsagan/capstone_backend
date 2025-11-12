<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CharityPost;
use App\Models\Charity;
use Illuminate\Support\Facades\DB;

class CharityPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all charities
        $charities = Charity::all();

        if ($charities->isEmpty()) {
            $this->command->warn('No charities found. Please run CharitySeeder first.');
            return;
        }

        $this->command->info('Creating charity posts...');

        foreach ($charities as $charity) {
            // Create 3-5 sample posts for each charity
            $postCount = rand(3, 5);

            for ($i = 1; $i <= $postCount; $i++) {
                $publishedAt = now()->subDays(rand(1, 30));
                
                CharityPost::create([
                    'charity_id' => $charity->id,
                    'title' => "Update #{$i} - " . $this->getRandomTitle(),
                    'content' => $this->getRandomContent($charity->name),
                    'image_path' => null,
                    'status' => rand(0, 10) > 2 ? 'published' : 'draft', // 80% published
                    'published_at' => rand(0, 10) > 2 ? $publishedAt : null,
                    'likes_count' => rand(0, 50),
                    'comments_count' => rand(0, 20),
                    'created_at' => $publishedAt,
                    'updated_at' => $publishedAt,
                ]);
            }

            $this->command->info("Created {$postCount} posts for {$charity->name}");
        }

        $totalPosts = CharityPost::count();
        $this->command->info("✅ Successfully created {$totalPosts} charity posts!");
    }

    private function getRandomTitle(): string
    {
        $titles = [
            'Making a Difference in Our Community',
            'Thank You to Our Amazing Donors',
            'New Initiative Launched',
            'Successful Fundraising Event',
            'Impact Report and Achievements',
            'Volunteer Appreciation Day',
            'Building Hope Together',
            'Expanding Our Reach',
            'Community Outreach Program',
            'Celebrating Milestones',
            'Important Announcement',
            'Behind the Scenes',
            'Success Stories from the Field',
            'Our Journey Continues',
            'Transforming Lives Daily',
        ];

        return $titles[array_rand($titles)];
    }

    private function getRandomContent(string $charityName): string
    {
        $contents = [
            "We are thrilled to share the incredible progress we've made this month! Thanks to your generous support, {$charityName} has been able to reach more beneficiaries and create lasting impact in our community. Your donations are changing lives every single day.",
            
            "Dear supporters, we wanted to take a moment to express our heartfelt gratitude for your continued trust and generosity. With your help, we've launched a new program that will benefit hundreds of families. Together, we are making the world a better place.",
            
            "Exciting news from {$charityName}! We've just completed another successful project that has brought smiles to many faces. Your contributions have made this possible, and we couldn't be more grateful for your partnership in this mission.",
            
            "This week has been remarkable for our organization. We've seen firsthand how your donations translate into real, tangible change. The stories we hear from the community inspire us to continue our work with even greater dedication.",
            
            "We're proud to announce that thanks to your support, {$charityName} has achieved another milestone. Every contribution, no matter the size, plays a crucial role in our mission. Thank you for believing in our cause and standing with us.",
            
            "A heartfelt thank you to everyone who participated in our recent fundraising event! The overwhelming support we received demonstrates the power of community coming together for a common good. Your generosity knows no bounds.",
            
            "As we reflect on our journey, we're filled with gratitude for supporters like you. {$charityName} continues to grow and evolve, always keeping our mission at the forefront. Your donations fuel our programs and enable us to serve those in need.",
            
            "Today, we want to share some inspiring stories from the field. Your contributions have helped us provide essential services to vulnerable populations. The impact you're making extends far beyond what words can express.",
        ];

        return $contents[array_rand($contents)];
    }
}
