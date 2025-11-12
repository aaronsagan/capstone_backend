<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Mail\CampaignCompletedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendCampaignCompletedEmails implements ShouldQueue
{
    use Queueable;

    public $campaign;

    /**
     * Create a new job instance.
     */
    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get all unique donors who contributed to this campaign
            $donors = $this->campaign->donations()
                ->where('status', 'completed')
                ->with('donor')
                ->get()
                ->pluck('donor')
                ->unique('id')
                ->filter(); // Remove null donors

            Log::info('Sending campaign completed emails', [
                'campaign_id' => $this->campaign->id,
                'donor_count' => $donors->count(),
            ]);

            // Send email to each donor
            foreach ($donors as $donor) {
                // Check notification preferences (add when implemented)
                Mail::to($donor->email)->queue(
                    new CampaignCompletedMail($this->campaign, $donor)
                );
            }

            Log::info('Campaign completed emails queued successfully', [
                'campaign_id' => $this->campaign->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send campaign completed emails', [
                'campaign_id' => $this->campaign->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
