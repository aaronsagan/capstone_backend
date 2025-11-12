<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\Support\Facades\DB;

class RecalculateCampaignTotals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:recalculate-totals 
                            {--campaign= : Specific campaign ID to recalculate}
                            {--all : Recalculate all campaigns}
                            {--check-only : Only check for discrepancies without fixing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate total_donations_received and donors_count for campaigns';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $campaignId = $this->option('campaign');
        $checkOnly = $this->option('check-only');
        $all = $this->option('all');

        if (!$campaignId && !$all) {
            $this->error('Please specify either --campaign=ID or --all');
            return 1;
        }

        if ($campaignId) {
            return $this->recalculateSingle($campaignId, $checkOnly);
        }

        return $this->recalculateAll($checkOnly);
    }

    /**
     * Recalculate a single campaign
     */
    private function recalculateSingle($campaignId, $checkOnly)
    {
        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            $this->error("Campaign ID {$campaignId} not found");
            return 1;
        }

        $this->info("Checking campaign: {$campaign->title}");

        $actual = $this->calculateActualTotals($campaign->id);
        $stored = [
            'total' => $campaign->total_donations_received,
            'donors' => $campaign->donors_count,
        ];

        $this->displayComparison($campaign, $stored, $actual);

        if ($actual['total'] != $stored['total'] || $actual['donors'] != $stored['donors']) {
            if ($checkOnly) {
                $this->warn('Discrepancy found! Run without --check-only to fix.');
            } else {
                $campaign->total_donations_received = $actual['total'];
                $campaign->donors_count = $actual['donors'];
                $campaign->timestamps = false;
                $campaign->save();
                $campaign->timestamps = true;
                
                $this->info('✓ Campaign totals updated successfully');
            }
            return 1;
        }

        $this->info('✓ Campaign totals are correct');
        return 0;
    }

    /**
     * Recalculate all campaigns
     */
    private function recalculateAll($checkOnly)
    {
        $this->info('Recalculating all campaigns...');
        
        $campaigns = Campaign::all();
        $discrepancies = 0;
        $fixed = 0;

        $progressBar = $this->output->createProgressBar($campaigns->count());

        foreach ($campaigns as $campaign) {
            $actual = $this->calculateActualTotals($campaign->id);
            $stored = [
                'total' => $campaign->total_donations_received,
                'donors' => $campaign->donors_count,
            ];

            if ($actual['total'] != $stored['total'] || $actual['donors'] != $stored['donors']) {
                $discrepancies++;
                
                if (!$checkOnly) {
                    $campaign->total_donations_received = $actual['total'];
                    $campaign->donors_count = $actual['donors'];
                    $campaign->timestamps = false;
                    $campaign->save();
                    $campaign->timestamps = true;
                    $fixed++;
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Total campaigns checked: {$campaigns->count()}");
        $this->info("Discrepancies found: {$discrepancies}");

        if ($checkOnly) {
            if ($discrepancies > 0) {
                $this->warn("Run without --check-only to fix the discrepancies");
            } else {
                $this->info("✓ All campaign totals are correct!");
            }
        } else {
            $this->info("✓ Fixed: {$fixed} campaigns");
        }

        return $discrepancies > 0 ? 1 : 0;
    }

    /**
     * Calculate actual totals from donations
     */
    private function calculateActualTotals($campaignId)
    {
        $result = Donation::where('campaign_id', $campaignId)
            ->where('status', 'completed')
            ->selectRaw('COALESCE(SUM(amount), 0) as total, COUNT(DISTINCT donor_id) as donors')
            ->first();

        return [
            'total' => (float) $result->total,
            'donors' => (int) $result->donors,
        ];
    }

    /**
     * Display comparison between stored and actual values
     */
    private function displayComparison($campaign, $stored, $actual)
    {
        $this->table(
            ['Metric', 'Stored in DB', 'Actual from Donations', 'Match'],
            [
                [
                    'Total Donations',
                    '₱' . number_format($stored['total'], 2),
                    '₱' . number_format($actual['total'], 2),
                    $stored['total'] == $actual['total'] ? '✓' : '✗'
                ],
                [
                    'Donors Count',
                    $stored['donors'],
                    $actual['donors'],
                    $stored['donors'] == $actual['donors'] ? '✓' : '✗'
                ],
            ]
        );
    }
}
