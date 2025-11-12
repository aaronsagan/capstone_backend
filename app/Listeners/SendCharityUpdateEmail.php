<?php

namespace App\Listeners;

use App\Events\CampaignCreated;
use App\Mail\Engagement\CharityUpdateNotificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendCharityUpdateEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CampaignCreated $event): void
    {
        try {
            $campaign = $event->campaign;
            $charity = $campaign->charity;
            
            if (!$charity) {
                Log::warning('Campaign has no charity', ['campaign_id' => $campaign->id]);
                return;
            }

            // Get all followers of this charity
            $followers = $charity->followers()->get();

            if ($followers->isEmpty()) {
                Log::info('No followers to notify', ['charity_id' => $charity->id]);
                return;
            }

            // Send email to each follower (queued)
            foreach ($followers as $follower) {
                // Check if user has this notification enabled
                // For now, send to all followers
                Mail::to($follower->email)->queue(
                    new CharityUpdateNotificationMail($follower, $campaign, $charity)
                );
            }

            Log::info('Charity update emails queued', [
                'charity_id' => $charity->id,
                'campaign_id' => $campaign->id,
                'followers_count' => $followers->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue charity update emails', [
                'campaign_id' => $event->campaign->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }
}
