<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\DonorMilestone;
use App\Models\Donation;

class RefreshDonorMilestones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'donor:refresh-milestones {donor_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate and update donor milestones achievements';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $donorId = $this->argument('donor_id');

        if ($donorId) {
            $donors = User::where('id', $donorId)->where('role', 'donor')->get();
            if ($donors->isEmpty()) {
                $this->error("Donor with ID {$donorId} not found.");
                return 1;
            }
        } else {
            $donors = User::where('role', 'donor')->whereNotNull('email_verified_at')->get();
        }

        $this->info("Evaluating milestones for {$donors->count()} donor(s)...");
        $progressBar = $this->output->createProgressBar($donors->count());

        $achievedCount = 0;

        foreach ($donors as $donor) {
            $achieved = $this->evaluateDonorMilestones($donor);
            $achievedCount += $achieved;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Milestone evaluation complete!");
        $this->info("{$achievedCount} new milestones achieved.");

        return 0;
    }

    /**
     * Evaluate milestones for a specific donor
     */
    private function evaluateDonorMilestones(User $donor): int
    {
        $achievedCount = 0;

        // Get donor stats
        $totalDonated = Donation::where('donor_id', $donor->id)
            ->whereIn('status', ['completed', 'auto_verified', 'manual_verified'])
            ->sum('amount');

        $donationCount = Donation::where('donor_id', $donor->id)
            ->whereIn('status', ['completed', 'auto_verified', 'manual_verified'])
            ->count();

        $campaignsSupported = Donation::where('donor_id', $donor->id)
            ->whereIn('status', ['completed', 'auto_verified', 'manual_verified'])
            ->distinct('campaign_id')
            ->count('campaign_id');

        $memberSince = $donor->created_at;
        $daysSinceMember = now()->diffInDays($memberSince);

        // Get unachieved milestones
        $milestones = DonorMilestone::where('donor_id', $donor->id)
            ->whereNull('achieved_at')
            ->get();

        foreach ($milestones as $milestone) {
            $shouldAchieve = false;

            switch ($milestone->key) {
                case 'first_donation':
                    $shouldAchieve = $donationCount >= 1;
                    break;

                case 'total_1000':
                    $shouldAchieve = $totalDonated >= 1000;
                    if (!$shouldAchieve) {
                        $milestone->meta = ['progress' => min(100, ($totalDonated / 1000) * 100)];
                        $milestone->save();
                    }
                    break;

                case 'total_10000':
                    $shouldAchieve = $totalDonated >= 10000;
                    if (!$shouldAchieve) {
                        $milestone->meta = ['progress' => min(100, ($totalDonated / 10000) * 100)];
                        $milestone->save();
                    }
                    break;

                case 'total_50000':
                    $shouldAchieve = $totalDonated >= 50000;
                    if (!$shouldAchieve) {
                        $milestone->meta = ['progress' => min(100, ($totalDonated / 50000) * 100)];
                        $milestone->save();
                    }
                    break;

                case 'total_100000':
                    $shouldAchieve = $totalDonated >= 100000;
                    if (!$shouldAchieve) {
                        $milestone->meta = ['progress' => min(100, ($totalDonated / 100000) * 100)];
                        $milestone->save();
                    }
                    break;

                case 'supported_5_campaigns':
                    $shouldAchieve = $campaignsSupported >= 5;
                    if (!$shouldAchieve) {
                        $milestone->meta = ['progress' => min(100, ($campaignsSupported / 5) * 100)];
                        $milestone->save();
                    }
                    break;

                case 'supported_10_campaigns':
                    $shouldAchieve = $campaignsSupported >= 10;
                    if (!$shouldAchieve) {
                        $milestone->meta = ['progress' => min(100, ($campaignsSupported / 10) * 100)];
                        $milestone->save();
                    }
                    break;

                case 'supported_25_campaigns':
                    $shouldAchieve = $campaignsSupported >= 25;
                    if (!$shouldAchieve) {
                        $milestone->meta = ['progress' => min(100, ($campaignsSupported / 25) * 100)];
                        $milestone->save();
                    }
                    break;

                case 'donations_10':
                    $shouldAchieve = $donationCount >= 10;
                    if (!$shouldAchieve) {
                        $milestone->meta = ['progress' => min(100, ($donationCount / 10) * 100)];
                        $milestone->save();
                    }
                    break;

                case 'donations_25':
                    $shouldAchieve = $donationCount >= 25;
                    if (!$shouldAchieve) {
                        $milestone->meta = ['progress' => min(100, ($donationCount / 25) * 100)];
                        $milestone->save();
                    }
                    break;

                case 'donations_50':
                    $shouldAchieve = $donationCount >= 50;
                    if (!$shouldAchieve) {
                        $milestone->meta = ['progress' => min(100, ($donationCount / 50) * 100)];
                        $milestone->save();
                    }
                    break;

                case 'member_1_year':
                    $shouldAchieve = $daysSinceMember >= 365;
                    if (!$shouldAchieve) {
                        $milestone->meta = ['progress' => min(100, ($daysSinceMember / 365) * 100)];
                        $milestone->save();
                    }
                    break;

                case 'verified_donor':
                    $shouldAchieve = $donor->email_verified_at !== null && $donationCount >= 1;
                    break;
            }

            if ($shouldAchieve) {
                $milestone->achieved_at = now();
                $milestone->meta = ['progress' => 100];
                $milestone->save();
                $achievedCount++;
            }
        }

        return $achievedCount;
    }
}
