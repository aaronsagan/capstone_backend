<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Mail\NewCampaignNotificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendNewCampaignNotifications implements ShouldQueue
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
            // Get all followers of this charity (users who follow this charity)
            $followers = $this->campaign->charity->activeFollowers()
                ->with('user')
                ->get()
                ->pluck('user')
                ->filter(); // Remove null users

            Log::info('Sending new campaign notifications', [
                'campaign_id' => $this->campaign->id,
                'charity_id' => $this->campaign->charity_id,
                'follower_count' => $followers->count(),
            ]);

            // Send email to each follower
            foreach ($followers as $follower) {
                // Check notification preferences (add when implemented)
                // Skip if user has disabled campaign notifications
                
                Mail::to($follower->email)->queue(
                    new NewCampaignNotificationMail($this->campaign, $follower)
                );
            }

            Log::info('New campaign notifications queued successfully', [
                'campaign_id' => $this->campaign->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send new campaign notifications', [
                'campaign_id' => $this->campaign->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
