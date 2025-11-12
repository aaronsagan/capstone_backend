<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CloseCampaignsAtEndDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:close-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically close campaigns that have reached their end date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for campaigns to close...');

        $now = Carbon::now();

        // Find published campaigns that have passed their end date or deadline
        $campaigns = Campaign::where('status', 'published')
            ->where(function($query) use ($now) {
                $query->where(function($q) use ($now) {
                    // Check end_date
                    $q->whereNotNull('end_date')
                      ->whereDate('end_date', '<', $now->toDateString());
                })->orWhere(function($q) use ($now) {
                    // Check deadline_at
                    $q->whereNotNull('deadline_at')
                      ->where('deadline_at', '<', $now);
                });
            })
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info('No campaigns to close.');
            return 0;
        }

        $this->info("Found {$campaigns->count()} campaign(s) to close.");

        $closed = 0;
        $errors = 0;

        foreach ($campaigns as $campaign) {
            try {
                $this->info("Closing: {$campaign->title} (ID: {$campaign->id})");

                $campaign->update(['status' => 'closed']);

                Log::info('Campaign automatically closed', [
                    'campaign_id' => $campaign->id,
                    'campaign_title' => $campaign->title,
                    'end_date' => $campaign->end_date,
                    'deadline_at' => $campaign->deadline_at,
                    'closed_at' => $now,
                ]);

                $closed++;
                $this->info("✓ Campaign closed successfully");

            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Error closing campaign {$campaign->id}: {$e->getMessage()}");
                Log::error('Campaign closure error', [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $this->info("\n=== Summary ===");
        $this->info("Closed: {$closed}");
        $this->info("Errors: {$errors}");
        $this->info("Total: {$campaigns->count()}");

        return 0;
    }
}
