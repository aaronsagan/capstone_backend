<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;

class ActivityLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please seed users first.');
            return;
        }

        $this->command->info('Creating activity logs...');

        // Sample activities for different user types
        $activities = [];

        foreach ($users as $user) {
            // Login activity for all users
            $activities[] = [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'action' => 'login',
                'details' => json_encode([
                    'login_method' => 'password',
                    'event_type' => 'authentication',
                ]),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'session_id' => \Illuminate\Support\Str::random(40),
                'created_at' => Carbon::now()->subHours(rand(1, 48)),
                'updated_at' => Carbon::now()->subHours(rand(1, 48)),
            ];

            // Registration activity
            $activities[] = [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'action' => 'register',
                'details' => json_encode([
                    'role' => $user->role,
                    'registration_method' => 'email',
                    'event_type' => 'authentication',
                ]),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'session_id' => \Illuminate\Support\Str::random(40),
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
                'updated_at' => Carbon::now()->subDays(rand(1, 30)),
            ];

            // Role-specific activities
            if ($user->role === 'donor') {
                // Donation activity
                $activities[] = [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'action' => 'donation_created',
                    'details' => json_encode([
                        'amount' => rand(100, 10000),
                        'is_recurring' => false,
                        'is_anonymous' => false,
                    ]),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'session_id' => \Illuminate\Support\Str::random(40),
                    'created_at' => Carbon::now()->subHours(rand(1, 24)),
                    'updated_at' => Carbon::now()->subHours(rand(1, 24)),
                ];

                // Profile update
                $activities[] = [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'action' => 'profile_updated',
                    'details' => json_encode([
                        'updated_fields' => ['phone', 'address'],
                        'user_role' => 'donor',
                    ]),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'session_id' => \Illuminate\Support\Str::random(40),
                    'created_at' => Carbon::now()->subDays(rand(1, 7)),
                    'updated_at' => Carbon::now()->subDays(rand(1, 7)),
                ];
            }

            if ($user->role === 'charity_admin') {
                // Campaign creation
                $activities[] = [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'action' => 'campaign_created',
                    'details' => json_encode([
                        'campaign_title' => 'Help Feed Hungry Children',
                        'campaign_type' => 'feeding_program',
                        'target_amount' => 50000,
                    ]),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'session_id' => \Illuminate\Support\Str::random(40),
                    'created_at' => Carbon::now()->subDays(rand(1, 14)),
                    'updated_at' => Carbon::now()->subDays(rand(1, 14)),
                ];

                // Campaign update
                $activities[] = [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'action' => 'campaign_updated',
                    'details' => json_encode([
                        'campaign_title' => 'Help Feed Hungry Children',
                        'updated_fields' => ['description', 'target_amount'],
                    ]),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'session_id' => \Illuminate\Support\Str::random(40),
                    'created_at' => Carbon::now()->subDays(rand(1, 7)),
                    'updated_at' => Carbon::now()->subDays(rand(1, 7)),
                ];
            }

            if ($user->role === 'admin') {
                // Charity approval
                $activities[] = [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'action' => 'charity_approved',
                    'details' => json_encode([
                        'charity_name' => 'Sample Charity Organization',
                        'approved_at' => Carbon::now()->toISOString(),
                    ]),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'session_id' => \Illuminate\Support\Str::random(40),
                    'created_at' => Carbon::now()->subDays(rand(1, 14)),
                    'updated_at' => Carbon::now()->subDays(rand(1, 14)),
                ];
            }

            // Logout activity
            $activities[] = [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'action' => 'logout',
                'details' => json_encode([
                    'logout_method' => 'manual',
                    'event_type' => 'authentication',
                ]),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'session_id' => \Illuminate\Support\Str::random(40),
                'created_at' => Carbon::now()->subMinutes(rand(10, 120)),
                'updated_at' => Carbon::now()->subMinutes(rand(10, 120)),
            ];
        }

        // Insert all activities
        ActivityLog::insert($activities);

        $this->command->info('Created ' . count($activities) . ' activity logs successfully!');
    }
}
