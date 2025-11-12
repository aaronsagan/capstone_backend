<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Charity;
use App\Models\DonorProfile;
use App\Models\Campaign;
use App\Models\Donation;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "🌱 Starting Test Data Seeding...\n\n";
        
        // Clear existing test data
        echo "🗑️  Cleaning up existing test data...\n";
        
        // Get test user IDs
        $testUserIds = User::where(function($query) {
            $query->where('email', 'like', 'testdonor%@charityhub.com')
                  ->orWhere('email', 'like', 'testcharity%@charityhub.com');
        })->pluck('id')->toArray();
        
        if (!empty($testUserIds)) {
            // Delete related donations first (foreign key constraints)
            DB::table('donations')->whereIn('donor_id', $testUserIds)->delete();
            
            // Delete campaigns (through charities)
            $charityIds = DB::table('charities')->whereIn('owner_id', $testUserIds)->pluck('id')->toArray();
            if (!empty($charityIds)) {
                DB::table('campaigns')->whereIn('charity_id', $charityIds)->delete();
            }
            
            // Delete donor profiles
            DB::table('donor_profiles')->whereIn('user_id', $testUserIds)->delete();
            
            // Delete charities
            DB::table('charities')->whereIn('owner_id', $testUserIds)->delete();
            
            // Delete users
            User::whereIn('id', $testUserIds)->delete();
            
            echo "   ✓ Deleted existing test accounts and related data\n\n";
        }

        echo "📝 Creating new test data...\n\n";

        // Create 5 Donors
        $donors = $this->createDonors();
        echo "✅ Created 5 donors\n";

        // Create 5 Charities
        $charities = $this->createCharities();
        echo "✅ Created 5 charities\n";

        // Create Campaigns for each charity
        $campaigns = $this->createCampaigns($charities);
        echo "✅ Created " . count($campaigns) . " campaigns\n";

        // Create 20 Donations
        $donations = $this->createDonations($donors, $campaigns, $charities);
        echo "✅ Created " . count($donations) . " donations\n\n";

        // Print credentials
        $this->printCredentials($donors, $charities);
    }

    private function createDonors(): array
    {
        $donors = [];
        
        $donorData = [
            [
                'name' => 'Maria Santos',
                'email' => 'testdonor1@charityhub.com',
                'phone' => '+639171234567',
                'city' => 'Manila',
                'province' => 'Metro Manila',
                'barangay' => 'Ermita',
                'occupation' => 'Software Engineer',
                'company' => 'Tech Solutions Inc.',
                'interests' => ['Education', 'Health', 'Environment'],
            ],
            [
                'name' => 'Juan Dela Cruz',
                'email' => 'testdonor2@charityhub.com',
                'phone' => '+639182345678',
                'city' => 'Quezon City',
                'province' => 'Metro Manila',
                'barangay' => 'Diliman',
                'occupation' => 'Business Owner',
                'company' => 'Cruz Enterprises',
                'interests' => ['Poverty Alleviation', 'Children'],
            ],
            [
                'name' => 'Ana Reyes',
                'email' => 'testdonor3@charityhub.com',
                'phone' => '+639193456789',
                'city' => 'Makati',
                'province' => 'Metro Manila',
                'barangay' => 'Poblacion',
                'occupation' => 'Marketing Manager',
                'company' => 'Global Marketing Corp',
                'interests' => ['Women Empowerment', 'Education'],
            ],
            [
                'name' => 'Carlos Garcia',
                'email' => 'testdonor4@charityhub.com',
                'phone' => '+639204567890',
                'city' => 'Pasig',
                'province' => 'Metro Manila',
                'barangay' => 'Kapitolyo',
                'occupation' => 'Doctor',
                'company' => 'Medical City Hospital',
                'interests' => ['Health', 'Medical Assistance'],
            ],
            [
                'name' => 'Sofia Mendoza',
                'email' => 'testdonor5@charityhub.com',
                'phone' => '+639215678901',
                'city' => 'Taguig',
                'province' => 'Metro Manila',
                'barangay' => 'BGC',
                'occupation' => 'Lawyer',
                'company' => 'Mendoza Law Firm',
                'interests' => ['Legal Aid', 'Human Rights', 'Education'],
            ],
        ];

        foreach ($donorData as $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password123'),
                'role' => 'donor',
                'email_verified_at' => now(),
                'phone' => $data['phone'],
                'created_at' => now()->subDays(rand(30, 180)),
                'updated_at' => now(),
            ]);

            $nameParts = explode(' ', $data['name']);
            DonorProfile::create([
                'user_id' => $user->id,
                'first_name' => $nameParts[0],
                'middle_name' => count($nameParts) > 2 ? $nameParts[1] : null,
                'last_name' => $nameParts[count($nameParts) - 1],
                'date_of_birth' => Carbon::now()->subYears(rand(25, 55))->format('Y-m-d'),
                'gender' => ['male', 'female'][rand(0, 1)],
                'street_address' => rand(1, 100) . ' ' . ['Rizal St', 'Bonifacio Ave', 'Luna St', 'Mabini St', 'Quezon Blvd'][rand(0, 4)],
                'barangay' => $data['barangay'],
                'city' => $data['city'],
                'province' => $data['province'],
                'region' => 'NCR',
                'postal_code' => '1' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT),
                'country' => 'Philippines',
                'full_address' => rand(1, 100) . ' Street Name, ' . $data['barangay'] . ', ' . $data['city'] . ', ' . $data['province'],
                'cause_preferences' => json_encode($data['interests']),
                'pref_email' => true,
                'pref_sms' => rand(0, 1) == 1,
                'pref_updates' => true,
                'pref_urgent' => true,
                'pref_reports' => rand(0, 1) == 1,
                'created_at' => $user->created_at,
                'updated_at' => now(),
            ]);

            $donors[] = $user;
        }

        return $donors;
    }

    private function createCharities(): array
    {
        $charities = [];
        
        $charityData = [
            [
                'name' => 'Hope Foundation Philippines',
                'email' => 'testcharity1@charityhub.com',
                'acronym' => 'HFP',
                'description' => 'Dedicated to providing educational opportunities for underprivileged children across the Philippines.',
                'mission' => 'To empower Filipino youth through quality education and character development.',
                'vision' => 'A Philippines where every child has access to quality education.',
                'focus_areas' => ['Education', 'Youth Development', 'Scholarship Programs'],
                'city' => 'Manila',
                'province' => 'Metro Manila',
            ],
            [
                'name' => 'Healing Hands Medical Mission',
                'email' => 'testcharity2@charityhub.com',
                'acronym' => 'HHMM',
                'description' => 'Providing free medical services and healthcare to underserved communities.',
                'mission' => 'To deliver accessible healthcare to those who need it most.',
                'vision' => 'Healthy communities across the Philippines.',
                'focus_areas' => ['Healthcare', 'Medical Assistance', 'Community Health'],
                'city' => 'Quezon City',
                'province' => 'Metro Manila',
            ],
            [
                'name' => 'Green Earth Alliance',
                'email' => 'testcharity3@charityhub.com',
                'acronym' => 'GEA',
                'description' => 'Environmental conservation and sustainability advocacy organization.',
                'mission' => 'To protect and restore the Philippine environment through community action.',
                'vision' => 'A sustainable and thriving ecosystem for future generations.',
                'focus_areas' => ['Environment', 'Climate Change', 'Conservation'],
                'city' => 'Makati',
                'province' => 'Metro Manila',
            ],
            [
                'name' => 'Feed the Children PH',
                'email' => 'testcharity4@charityhub.com',
                'acronym' => 'FTCPH',
                'description' => 'Fighting child hunger and malnutrition through feeding programs.',
                'mission' => 'To ensure no child goes to bed hungry in the Philippines.',
                'vision' => 'Zero child hunger in our communities.',
                'focus_areas' => ['Nutrition', 'Child Welfare', 'Food Security'],
                'city' => 'Pasig',
                'province' => 'Metro Manila',
            ],
            [
                'name' => 'Disaster Relief Network',
                'email' => 'testcharity5@charityhub.com',
                'acronym' => 'DRN',
                'description' => 'Rapid response and recovery assistance for disaster-affected communities.',
                'mission' => 'To provide immediate relief and long-term recovery support to disaster victims.',
                'vision' => 'Resilient communities prepared for and recovering from disasters.',
                'focus_areas' => ['Disaster Relief', 'Emergency Response', 'Community Resilience'],
                'city' => 'Taguig',
                'province' => 'Metro Manila',
            ],
        ];

        foreach ($charityData as $index => $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('charity123'),
                'role' => 'charity_admin',
                'email_verified_at' => now(),
                'phone' => '+6328' . str_pad($index + 1, 7, '0', STR_PAD_LEFT),
                'created_at' => now()->subDays(rand(90, 365)),
                'updated_at' => now(),
            ]);

            $charity = Charity::create([
                'owner_id' => $user->id,
                'name' => $data['name'],
                'reg_no' => 'SEC-' . rand(10000, 99999) . '-' . date('Y'),
                'tax_id' => rand(100000000, 999999999),
                'mission' => $data['mission'],
                'vision' => $data['vision'],
                'website' => 'https://' . strtolower(str_replace(' ', '', $data['acronym'])) . '.org.ph',
                'contact_email' => $data['email'],
                'contact_phone' => '+6328' . str_pad($index + 1, 7, '0', STR_PAD_LEFT),
                'verification_status' => 'approved',
                'verified_at' => now(),
                'verification_notes' => 'Verified test account',
                'created_at' => $user->created_at,
                'updated_at' => now(),
            ]);

            $charities[] = [
                'user' => $user,
                'charity' => $charity,
            ];
        }

        return $charities;
    }

    private function createCampaigns(array $charities): array
    {
        $campaigns = [];
        
        $campaignTemplates = [
            [
                'title' => 'Back to School Program 2025',
                'description' => 'Provide school supplies, uniforms, and educational materials for 500 underprivileged students.',
                'goal' => 250000,
                'type' => 'project',
            ],
            [
                'title' => 'Free Medical Mission',
                'description' => 'Organize free medical check-ups, consultations, and medicine distribution for indigent families.',
                'goal' => 150000,
                'type' => 'project',
            ],
            [
                'title' => 'Tree Planting Initiative',
                'description' => 'Plant 10,000 trees in deforested areas to combat climate change and restore ecosystems.',
                'goal' => 100000,
                'type' => 'project',
            ],
            [
                'title' => 'Feeding Program',
                'description' => 'Provide nutritious meals for 1,000 malnourished children in underserved communities.',
                'goal' => 200000,
                'type' => 'project',
            ],
            [
                'title' => 'Emergency Relief Fund',
                'description' => 'Rapid response fund for disaster victims including food packs, water, and temporary shelter.',
                'goal' => 500000,
                'type' => 'emergency',
            ],
            [
                'title' => 'Scholarship Fund',
                'description' => 'Full scholarship grants for deserving but financially challenged college students.',
                'goal' => 300000,
                'type' => 'project',
            ],
            [
                'title' => 'Community Health Center',
                'description' => 'Build and equip a community health center in a remote barangay.',
                'goal' => 1000000,
                'type' => 'project',
            ],
            [
                'title' => 'Clean Water Project',
                'description' => 'Install water filtration systems in 20 communities without access to clean drinking water.',
                'goal' => 400000,
                'type' => 'project',
            ],
        ];

        foreach ($charities as $index => $charityData) {
            // Create 1-2 campaigns per charity
            $numCampaigns = rand(1, 2);
            
            for ($i = 0; $i < $numCampaigns; $i++) {
                $template = $campaignTemplates[($index * 2 + $i) % count($campaignTemplates)];
                
                $startDate = Carbon::now()->subDays(rand(30, 90));
                $endDate = $startDate->copy()->addDays(rand(60, 180));
                $currentAmount = rand($template['goal'] * 0.2, $template['goal'] * 0.8);
                
                $campaign = Campaign::create([
                    'charity_id' => $charityData['charity']->id,
                    'title' => $template['title'],
                    'description' => $template['description'],
                    'problem' => 'Many families in our community face challenges that require immediate support and intervention.',
                    'solution' => $template['description'],
                    'expected_outcome' => 'With your help, we can make a lasting positive impact on the lives of our beneficiaries and strengthen our community.',
                    'target_amount' => $template['goal'],
                    'deadline_at' => $endDate,
                    'status' => $endDate->isFuture() ? 'published' : 'closed',
                    'created_at' => $startDate,
                    'updated_at' => now(),
                ]);

                $campaigns[] = $campaign;
            }
        }

        return $campaigns;
    }

    private function createDonations(array $donors, array $campaigns, array $charities): array
    {
        $donations = [];
        
        for ($i = 0; $i < 20; $i++) {
            $donor = $donors[array_rand($donors)];
            $campaign = $campaigns[array_rand($campaigns)];
            $charity = collect($charities)->firstWhere('charity.id', $campaign->charity_id);
            
            $amount = [100, 250, 500, 1000, 2000, 5000, 10000][rand(0, 6)];
            $donatedAt = Carbon::now()->subDays(rand(1, 60));
            
            $statuses = ['completed', 'completed', 'completed', 'pending']; // 75% completed
            $status = $statuses[array_rand($statuses)];
            
            $donation = Donation::create([
                'donor_id' => $donor->id,
                'charity_id' => $charity['charity']->id,
                'campaign_id' => $campaign->id,
                'amount' => $amount,
                'purpose' => ['general', 'project', 'emergency'][rand(0, 2)],
                'is_anonymous' => rand(0, 5) == 0, // 20% anonymous
                'status' => $status,
                'proof_path' => null,
                'proof_type' => null,
                'external_ref' => 'TXN' . strtoupper(substr(md5(uniqid()), 0, 12)),
                'receipt_no' => $status === 'completed' ? 'RCP' . date('Ymd') . str_pad($i + 1, 6, '0', STR_PAD_LEFT) : null,
                'donated_at' => $donatedAt,
                'created_at' => $donatedAt,
                'updated_at' => now(),
            ]);

            // Note: Campaign updates would happen here if needed
            // (current_amount and donors_count tracking)

            $donations[] = $donation;
        }

        return $donations;
    }

    private function printCredentials(array $donors, array $charities): void
    {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📋 TEST ACCOUNT CREDENTIALS\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        echo "👥 DONOR ACCOUNTS (Password: password123)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        foreach ($donors as $index => $donor) {
            echo sprintf(
                "Donor %d: %-30s | %s\n",
                $index + 1,
                $donor->name,
                $donor->email
            );
        }

        echo "\n🏢 CHARITY ACCOUNTS (Password: charity123)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        foreach ($charities as $index => $charityData) {
            echo sprintf(
                "Charity %d: %-28s | %s\n",
                $index + 1,
                $charityData['charity']->name,
                $charityData['user']->email
            );
        }

        echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "✅ All test data has been created successfully!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        echo "📊 DATA SUMMARY:\n";
        echo "   • 5 Donor Accounts\n";
        echo "   • 5 Charity Organizations\n";
        echo "   • 8-10 Active Campaigns\n";
        echo "   • 20 Donations (mix of completed and pending)\n\n";
        
        echo "🧪 READY FOR TESTING:\n";
        echo "   ✓ Analytics and Reports\n";
        echo "   ✓ Donation History\n";
        echo "   ✓ Campaign Management\n";
        echo "   ✓ Donor Profiles\n";
        echo "   ✓ Charity Dashboards\n";
        echo "   ✓ Fund Tracking\n\n";
    }
}
