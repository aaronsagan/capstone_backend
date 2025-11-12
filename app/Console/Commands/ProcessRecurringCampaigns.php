<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessRecurringCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:process-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process recurring campaigns and create new occurrences';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing recurring campaigns...');

        // Find campaigns that are due for recurrence
        $campaigns = Campaign::dueForRecurrence()->get();

        if ($campaigns->isEmpty()) {
            $this->info('No campaigns due for recurrence.');
            return 0;
        }

        $this->info("Found {$campaigns->count()} campaign(s) to process.");

        $processed = 0;
        $errors = 0;

        foreach ($campaigns as $campaign) {
            try {
                $this->info("Processing: {$campaign->title} (ID: {$campaign->id})");

                // Create new occurrence
                $newCampaign = $this->createNewOccurrence($campaign);

                // Update next occurrence date for parent campaign
                $this->updateNextOccurrence($campaign);

                $processed++;
                $this->info("✓ Created new occurrence (ID: {$newCampaign->id})");

            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Error processing campaign {$campaign->id}: {$e->getMessage()}");
                Log::error('Recurring campaign processing error', [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $this->info("\n=== Summary ===");
        $this->info("Processed: {$processed}");
        $this->info("Errors: {$errors}");
        $this->info("Total: {$campaigns->count()}");

        return 0;
    }

    /**
     * Create a new occurrence of a recurring campaign
     */
    private function createNewOccurrence(Campaign $parentCampaign)
    {
        // Determine occurrence number
        $occurrenceNumber = $parentCampaign->childCampaigns()->count() + 1;

        // Prepare new campaign data
        $newCampaignData = [
            'charity_id' => $parentCampaign->charity_id,
            'title' => $parentCampaign->title . " (Occurrence #{$occurrenceNumber})",
            'description' => $parentCampaign->description,
            'problem' => $parentCampaign->problem,
            'solution' => $parentCampaign->solution,
            'expected_outcome' => $parentCampaign->expected_outcome,
            'beneficiary' => $parentCampaign->beneficiary,
            'beneficiary_category' => $parentCampaign->beneficiary_category,
            'region' => $parentCampaign->region,
            'province' => $parentCampaign->province,
            'city' => $parentCampaign->city,
            'barangay' => $parentCampaign->barangay,
            'target_amount' => $parentCampaign->target_amount,
            'cover_image_path' => $parentCampaign->cover_image_path,
            'campaign_type' => $parentCampaign->campaign_type,
            'donation_type' => $parentCampaign->donation_type,
            
            // Set status based on auto_publish setting
            'status' => $parentCampaign->auto_publish ? 'published' : 'draft',
            
            // Set dates for this occurrence
            'start_date' => $parentCampaign->next_occurrence_date,
            'end_date' => $this->calculateEndDate(
                $parentCampaign->next_occurrence_date,
                $parentCampaign->recurrence_type,
                $parentCampaign->recurrence_interval
            ),
            'deadline_at' => $this->calculateDeadline(
                $parentCampaign->next_occurrence_date,
                $parentCampaign->recurrence_type,
                $parentCampaign->recurrence_interval
            ),
            
            // Link to parent and mark as child campaign
            'parent_campaign_id' => $parentCampaign->id,
            'occurrence_number' => $occurrenceNumber,
            'is_recurring' => false, // Child campaigns are not recurring themselves
        ];

        // Create the new campaign
        $newCampaign = Campaign::create($newCampaignData);

        // Copy donation channels from parent
        $channelIds = $parentCampaign->donationChannels()->pluck('donation_channels.id')->toArray();
        if (!empty($channelIds)) {
            $newCampaign->donationChannels()->attach($channelIds);
        }

        Log::info('Created recurring campaign occurrence', [
            'parent_id' => $parentCampaign->id,
            'new_campaign_id' => $newCampaign->id,
            'occurrence_number' => $occurrenceNumber,
        ]);

        return $newCampaign;
    }

    /**
     * Update the next occurrence date for the parent campaign
     */
    private function updateNextOccurrence(Campaign $campaign)
    {
        $nextDate = $this->calculateNextOccurrence(
            $campaign->next_occurrence_date,
            $campaign->recurrence_type,
            $campaign->recurrence_interval
        );

        // Check if next occurrence would be after end date
        if ($campaign->recurrence_end_date && $nextDate->gt($campaign->recurrence_end_date)) {
            // Stop recurring by setting next_occurrence_date to null
            $campaign->update(['next_occurrence_date' => null]);
            Log::info('Recurring campaign ended', [
                'campaign_id' => $campaign->id,
                'end_date' => $campaign->recurrence_end_date,
            ]);
        } else {
            $campaign->update(['next_occurrence_date' => $nextDate]);
        }
    }

    /**
     * Calculate next occurrence date
     */
    private function calculateNextOccurrence($currentDate, $recurrenceType, $interval = 1)
    {
        $date = Carbon::parse($currentDate);
        
        switch ($recurrenceType) {
            case 'weekly':
                return $date->addWeeks($interval);
            case 'monthly':
                return $date->addMonths($interval);
            case 'quarterly':
                return $date->addMonths(3 * $interval);
            case 'yearly':
                return $date->addYears($interval);
            default:
                return $date->addMonths($interval);
        }
    }

    /**
     * Calculate end date for the occurrence
     */
    private function calculateEndDate($startDate, $recurrenceType, $interval = 1)
    {
        $date = Carbon::parse($startDate);
        
        // End date is one day before the next occurrence
        switch ($recurrenceType) {
            case 'weekly':
                return $date->addWeeks($interval)->subDay();
            case 'monthly':
                return $date->addMonths($interval)->subDay();
            case 'quarterly':
                return $date->addMonths(3 * $interval)->subDay();
            case 'yearly':
                return $date->addYears($interval)->subDay();
            default:
                return $date->addMonths($interval)->subDay();
        }
    }

    /**
     * Calculate deadline for the occurrence
     */
    private function calculateDeadline($startDate, $recurrenceType, $interval = 1)
    {
        // Deadline is the same as end date but as datetime
        return $this->calculateEndDate($startDate, $recurrenceType, $interval)->endOfDay();
    }
}
