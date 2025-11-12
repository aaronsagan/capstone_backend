<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\Support\Facades\DB;

class ValidateCampaignData extends Command
{
    protected $signature = 'campaigns:validate 
                            {--fix : Automatically fix issues found}
                            {--campaign= : Validate specific campaign ID}';

    protected $description = 'Validate campaign data integrity and optionally fix issues';

    private $issues = [];
    private $fixed = 0;

    public function handle()
    {
        $campaignId = $this->option('campaign');
        $autoFix = $this->option('fix');

        $this->info('Starting campaign data validation...');
        $this->newLine();

        if ($campaignId) {
            $this->validateSingle($campaignId, $autoFix);
        } else {
            $this->validateAll($autoFix);
        }

        $this->displayResults($autoFix);

        return empty($this->issues) ? 0 : 1;
    }

    private function validateAll($autoFix)
    {
        $campaigns = Campaign::all();
        $bar = $this->output->createProgressBar($campaigns->count());

        foreach ($campaigns as $campaign) {
            $this->validateCampaign($campaign, $autoFix);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    private function validateSingle($campaignId, $autoFix)
    {
        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            $this->error("Campaign ID {$campaignId} not found");
            return;
        }

        $this->info("Validating campaign: {$campaign->title}");
        $this->validateCampaign($campaign, $autoFix);
    }

    private function validateCampaign($campaign, $autoFix)
    {
        // 1. Check charity exists
        if (!$campaign->charity) {
            $this->addIssue($campaign->id, 'Orphaned campaign - charity does not exist', 'critical');
        }

        // 2. Check category exists if set
        if ($campaign->category_id && !$campaign->category) {
            $this->addIssue($campaign->id, 'Invalid category_id - category does not exist', 'warning');
            if ($autoFix) {
                $this->fixIssue($campaign, 'category_id', null, 'Cleared invalid category_id');
            }
        }

        // 3. Validate recurring campaign data
        if ($campaign->is_recurring) {
            if (!$campaign->recurrence_type) {
                $this->addIssue($campaign->id, 'Recurring campaign missing recurrence_type', 'error');
                if ($autoFix) {
                    $this->fixIssue($campaign, 'recurrence_type', 'monthly', 'Set default recurrence_type');
                }
            }
            if (!$campaign->recurrence_start_date) {
                $this->addIssue($campaign->id, 'Recurring campaign missing recurrence_start_date', 'error');
                if ($autoFix) {
                    $date = \Carbon\Carbon::parse($campaign->created_at)->toDateString();
                    $this->fixIssue($campaign, 'recurrence_start_date', $date, 'Set recurrence_start_date to created_at');
                }
            }
        }

        // 4. Validate donation totals match
        $actualTotal = $campaign->donations()->where('status', 'completed')->sum('amount');
        $actualDonors = $campaign->donations()->where('status', 'completed')->distinct('donor_id')->count('donor_id');

        if (abs($actualTotal - $campaign->total_donations_received) > 0.01) {
            $this->addIssue(
                $campaign->id,
                "Donation total mismatch. DB: {$campaign->total_donations_received}, Actual: {$actualTotal}",
                'error'
            );
            if ($autoFix) {
                $this->fixIssue($campaign, 'total_donations_received', $actualTotal, 'Recalculated donation total');
            }
        }

        if ($actualDonors != $campaign->donors_count) {
            $this->addIssue(
                $campaign->id,
                "Donors count mismatch. DB: {$campaign->donors_count}, Actual: {$actualDonors}",
                'error'
            );
            if ($autoFix) {
                $this->fixIssue($campaign, 'donors_count', $actualDonors, 'Recalculated donors count');
            }
        }

        // 5. Validate negative values
        if ($campaign->total_donations_received < 0) {
            $this->addIssue($campaign->id, 'Negative total_donations_received', 'critical');
            if ($autoFix) {
                $this->fixIssue($campaign, 'total_donations_received', 0, 'Reset negative total to 0');
            }
        }

        if ($campaign->donors_count < 0) {
            $this->addIssue($campaign->id, 'Negative donors_count', 'critical');
            if ($autoFix) {
                $this->fixIssue($campaign, 'donors_count', 0, 'Reset negative count to 0');
            }
        }

        if ($campaign->target_amount !== null && $campaign->target_amount < 0) {
            $this->addIssue($campaign->id, 'Negative target_amount', 'error');
            if ($autoFix) {
                $this->fixIssue($campaign, 'target_amount', 0, 'Reset negative target to 0');
            }
        }

        // 6. Validate date logic
        if ($campaign->end_date && $campaign->start_date && $campaign->end_date < $campaign->start_date) {
            $this->addIssue($campaign->id, 'End date is before start date', 'error');
        }

        if ($campaign->recurrence_end_date && $campaign->recurrence_start_date && 
            $campaign->recurrence_end_date < $campaign->recurrence_start_date) {
            $this->addIssue($campaign->id, 'Recurrence end date is before start date', 'error');
        }

        // 7. Validate parent campaign exists
        if ($campaign->parent_campaign_id) {
            if ($campaign->parent_campaign_id == $campaign->id) {
                $this->addIssue($campaign->id, 'Campaign is its own parent', 'critical');
                if ($autoFix) {
                    $this->fixIssue($campaign, 'parent_campaign_id', null, 'Cleared self-referencing parent');
                }
            } elseif (!Campaign::find($campaign->parent_campaign_id)) {
                $this->addIssue($campaign->id, 'Parent campaign does not exist', 'error');
                if ($autoFix) {
                    $this->fixIssue($campaign, 'parent_campaign_id', null, 'Cleared non-existent parent');
                }
            }
        }

        // 8. Validate status
        $validStatuses = ['draft', 'published', 'closed', 'archived', 'paused'];
        if (!in_array($campaign->status, $validStatuses)) {
            $this->addIssue($campaign->id, "Invalid status: {$campaign->status}", 'critical');
            if ($autoFix) {
                $this->fixIssue($campaign, 'status', 'draft', 'Reset to draft status');
            }
        }

        // 9. Check location data completeness
        if ($campaign->region && (!$campaign->province || !$campaign->city)) {
            $this->addIssue($campaign->id, 'Incomplete location data (has region but missing province/city)', 'warning');
        }
    }

    private function addIssue($campaignId, $message, $severity)
    {
        $this->issues[] = [
            'campaign_id' => $campaignId,
            'message' => $message,
            'severity' => $severity,
        ];
    }

    private function fixIssue($campaign, $field, $value, $action)
    {
        DB::table('campaigns')
            ->where('id', $campaign->id)
            ->update([$field => $value]);
        
        $this->fixed++;
        $this->line("  ✓ {$action} for campaign {$campaign->id}");
    }

    private function displayResults($autoFix)
    {
        if (empty($this->issues)) {
            $this->info('✓ All campaigns validated successfully! No issues found.');
            return;
        }

        $this->newLine();
        $this->error("Found " . count($this->issues) . " issue(s):");
        $this->newLine();

        $grouped = collect($this->issues)->groupBy('severity');

        foreach (['critical', 'error', 'warning'] as $severity) {
            if ($grouped->has($severity)) {
                $count = $grouped[$severity]->count();
                $color = $severity === 'critical' ? 'red' : ($severity === 'error' ? 'yellow' : 'blue');
                
                $this->line("<fg={$color}>" . strtoupper($severity) . " ({$count}):</>");
                
                foreach ($grouped[$severity] as $issue) {
                    $this->line("  Campaign #{$issue['campaign_id']}: {$issue['message']}");
                }
                $this->newLine();
            }
        }

        if ($autoFix) {
            $this->info("✓ Fixed {$this->fixed} issue(s) automatically");
        } else {
            $this->warn("Run with --fix flag to automatically fix issues");
        }
    }
}
