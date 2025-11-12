<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Mail\Engagement\CampaignReminderMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendCampaignReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'campaigns:send-reminders';

    /**
     * The console command description.
     */
    protected $description = 'Send reminder emails for saved campaigns ending within 3 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for campaigns ending soon...');

        // Get campaigns ending within the next 3 days
        $endingSoon = Campaign::where('end_date', '>', now())
            ->where('end_date', '<=', now()->addDays(3))
            ->where('status', 'active')
            ->get();

        if ($endingSoon->isEmpty()) {
            $this->info('No campaigns ending soon.');
            return 0;
        }

        $this->info("Found {$endingSoon->count()} campaigns ending soon.");

        $totalEmailsSent = 0;

        foreach ($endingSoon as $campaign) {
            // Get donors who have saved/bookmarked this campaign
            // Assuming there's a saved_campaigns table or similar
            $savedDonors = DB::table('saved_campaigns')
                ->where('campaign_id', $campaign->id)
                ->pluck('user_id');

            if ($savedDonors->isEmpty()) {
                continue;
            }

            $donors = \App\Models\User::whereIn('id', $savedDonors)->get();

            foreach ($donors as $donor) {
                try {
                    Mail::to($donor->email)->queue(
                        new CampaignReminderMail($donor, $campaign)
                    );
                    $totalEmailsSent++;
                } catch (\Exception $e) {
                    Log::error('Failed to send campaign reminder', [
                        'campaign_id' => $campaign->id,
                        'donor_id' => $donor->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->info("Queued {$donors->count()} reminders for campaign: {$campaign->title}");
        }

        $this->info("Total emails queued: {$totalEmailsSent}");
        Log::info('Campaign reminders sent', ['total' => $totalEmailsSent]);

        return 0;
    }
}
