<?php

namespace App\Listeners;

use App\Events\CampaignProgressUpdated;
use App\Mail\Engagement\CampaignProgressMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SendCampaignProgressEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CampaignProgressUpdated $event): void
    {
        try {
            $campaign = $event->campaign;
            $milestone = $event->milestone;
            $percentage = $event->percentage;

            // Get all donors who have donated to this campaign
            $donors = DB::table('donations')
                ->where('campaign_id', $campaign->id)
                ->where('status', 'completed')
                ->distinct()
                ->pluck('donor_id');

            if ($donors->isEmpty()) {
                Log::info('No donors to notify for campaign progress', [
                    'campaign_id' => $campaign->id
                ]);
                return;
            }

            // Get unique donor users
            $donorUsers = \App\Models\User::whereIn('id', $donors)->get();

            foreach ($donorUsers as $donor) {
                Mail::to($donor->email)->queue(
                    new CampaignProgressMail($donor, $campaign, $percentage, $milestone)
                );
            }

            Log::info('Campaign progress emails queued', [
                'campaign_id' => $campaign->id,
                'milestone' => $milestone,
                'percentage' => $percentage,
                'donors_count' => $donorUsers->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue campaign progress emails', [
                'campaign_id' => $event->campaign->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }
}
