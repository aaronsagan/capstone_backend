<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Campaign;
use App\Models\Charity;
use Carbon\Carbon;

class HugsAndKissesCampaignsSeeder extends Seeder
{
    /**
     * Run the database seeds for Hugs and Kisses charity campaigns.
     */
    public function run(): void
    {
        // Find the charity by name
        $charity = Charity::where('name', 'Hugs and Kisses')->first();
        
        if (!$charity) {
            $this->command->error('❌ Charity "Hugs and Kisses" not found!');
            $this->command->info('💡 Please make sure the charity exists in the database.');
            return;
        }

        $this->command->info("✅ Found charity: {$charity->name} (ID: {$charity->id})");

        // Campaign 1: Educational Support for Underprivileged Children
        $campaign1 = Campaign::create([
            'charity_id' => $charity->id,
            'title' => 'Educational Support for Underprivileged Children',
            'description' => 'Help us provide school supplies, books, and educational materials to children from low-income families in Metro Manila. Every child deserves access to quality education, and your donation can make a significant difference in their future.',
            'problem' => 'Many children in underprivileged communities lack basic school supplies such as notebooks, pencils, bags, and uniforms. This prevents them from fully participating in their education and limits their potential for a better future.',
            'solution' => 'We will distribute comprehensive school supply packages to 200 children in identified communities. Each package includes: notebooks, pens, pencils, school bag, uniform, shoes, and basic hygiene kits. We will also provide quarterly book allowances to support their continuous learning.',
            'expected_outcome' => 'By providing these essential educational materials, we expect to see improved school attendance rates, better academic performance, and increased confidence among the beneficiary children. We aim to support 200 children throughout the school year 2024-2025.',
            'beneficiary' => 'Elementary and high school students from low-income families in Metro Manila',
            'beneficiary_category' => json_encode(['Children', 'Education', 'Low-Income Families']),
            'region' => 'NCR',
            'province' => 'Metro Manila',
            'city' => 'Quezon City',
            'barangay' => 'Payatas',
            'target_amount' => 500000.00,
            'deadline_at' => Carbon::now()->addMonths(3),
            'status' => 'published',
            'donation_type' => 'one_time',
            'campaign_type' => 'education',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(3),
            'is_recurring' => false,
        ]);

        $this->command->info("✅ Created Campaign 1: {$campaign1->title}");

        // Campaign 2: Medical Mission for Senior Citizens
        $campaign2 = Campaign::create([
            'charity_id' => $charity->id,
            'title' => 'Free Medical Mission for Senior Citizens',
            'description' => 'Organizing a comprehensive medical mission providing free consultations, medicine, and basic health services to senior citizens in underserved communities. Our goal is to ensure that our elderly population receives proper healthcare despite financial constraints.',
            'problem' => 'Senior citizens in low-income communities often cannot afford regular medical check-ups, maintenance medications, and basic health services. This leads to untreated conditions, preventable complications, and declining quality of life in their golden years.',
            'solution' => 'We will organize a 3-day medical mission featuring: free consultations with doctors (general practitioners, cardiologists, and ophthalmologists), free basic laboratory tests (blood sugar, blood pressure, cholesterol screening), provision of maintenance medications for chronic conditions, and distribution of vitamins and health supplements. We will also provide free eyeglasses for those with vision problems.',
            'expected_outcome' => 'We aim to serve at least 500 senior citizens across three barangays. Participants will receive complete health assessments, necessary medications for 3 months, and referrals for cases requiring specialized care. We expect to identify and address common health issues early, improving overall health outcomes.',
            'beneficiary' => 'Senior citizens aged 60 and above from low-income communities',
            'beneficiary_category' => json_encode(['Elderly', 'Healthcare', 'Community Health']),
            'region' => 'NCR',
            'province' => 'Metro Manila',
            'city' => 'Manila',
            'barangay' => 'Tondo',
            'target_amount' => 750000.00,
            'deadline_at' => Carbon::now()->addMonths(2),
            'status' => 'published',
            'donation_type' => 'one_time',
            'campaign_type' => 'medical',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(2),
            'is_recurring' => false,
        ]);

        $this->command->info("✅ Created Campaign 2: {$campaign2->title}");

        // Campaign 3: Nutrition Program for Malnourished Children
        $campaign3 = Campaign::create([
            'charity_id' => $charity->id,
            'title' => 'Monthly Nutrition Program for Malnourished Children',
            'description' => 'A sustainable nutrition program providing daily nutritious meals and health monitoring for malnourished children in urban poor communities. Together, we can help these children grow healthy and strong.',
            'problem' => 'Malnutrition remains a critical issue in many urban poor communities. Children suffer from stunted growth, weakened immune systems, and poor cognitive development due to lack of access to nutritious food. Many families struggle to provide even one proper meal a day for their children.',
            'solution' => 'We will establish a feeding program providing two nutritious meals daily (breakfast and lunch) for identified malnourished children aged 2-10 years old. Each meal is carefully planned by nutritionists to meet daily nutritional requirements. The program includes: daily feeding sessions, monthly health monitoring and weighing, nutrition education for parents, and provision of vitamins and supplements.',
            'expected_outcome' => 'Within 6 months, we aim to improve the nutritional status of 150 children, with measurable increases in weight and height. We expect to see: 80% of children reaching normal weight-for-age, improved school performance due to better nutrition, reduced incidence of common illnesses, and increased awareness among parents about proper nutrition.',
            'beneficiary' => 'Malnourished children aged 2-10 years old from urban poor communities',
            'beneficiary_category' => json_encode(['Children', 'Nutrition', 'Health', 'Food Security']),
            'region' => 'NCR',
            'province' => 'Metro Manila',
            'city' => 'Pasig City',
            'barangay' => 'Rosario',
            'target_amount' => 900000.00,
            'deadline_at' => Carbon::now()->addMonths(6),
            'status' => 'published',
            'donation_type' => 'recurring',
            'campaign_type' => 'feeding_program',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(6),
            'is_recurring' => true,
            'recurrence_type' => 'monthly',
            'recurrence_interval' => 1,
            'recurrence_start_date' => Carbon::now(),
            'recurrence_end_date' => Carbon::now()->addYear(),
            'next_occurrence_date' => Carbon::now()->addMonth(),
            'auto_publish' => true,
        ]);

        $this->command->info("✅ Created Campaign 3: {$campaign3->title}");

        $this->command->info('');
        $this->command->info('🎉 Successfully created 3 campaigns for Hugs and Kisses charity!');
        $this->command->info('');
        $this->command->table(
            ['ID', 'Title', 'Target Amount', 'Type', 'Status'],
            [
                [$campaign1->id, $campaign1->title, '₱' . number_format($campaign1->target_amount, 2), $campaign1->campaign_type, $campaign1->status],
                [$campaign2->id, $campaign2->title, '₱' . number_format($campaign2->target_amount, 2), $campaign2->campaign_type, $campaign2->status],
                [$campaign3->id, $campaign3->title, '₱' . number_format($campaign3->target_amount, 2), $campaign3->campaign_type, $campaign3->status],
            ]
        );
    }
}
