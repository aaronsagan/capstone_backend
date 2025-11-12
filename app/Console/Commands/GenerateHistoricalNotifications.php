<?php

namespace App\Console\Commands;

use App\Models\Donation;
use App\Models\Campaign;
use App\Models\User;
use App\Models\CharityFollow;
use App\Models\CampaignComment;
use App\Services\NotificationHelper;
use Illuminate\Console\Command;

class GenerateHistoricalNotifications extends Command
{
    protected $signature = 'notifications:generate-historical';
    protected $description = 'Generate notifications for existing historical data';

    public function handle()
    {
        $this->info('Generating historical notifications...');

        // Generate donation notifications
        $this->info('Processing donations...');
        $donations = Donation::with(['donor', 'charity'])->where('status', 'completed')->get();
        foreach ($donations as $donation) {
            try {
                if ($donation->donor) {
                    NotificationHelper::donationConfirmed($donation);
                }
                if ($donation->charity) {
                    NotificationHelper::donationReceived($donation);
                }
                $this->line("✓ Created notifications for donation #{$donation->id}");
            } catch (\Exception $e) {
                $this->error("✗ Failed for donation #{$donation->id}: " . $e->getMessage());
            }
        }

        // Generate campaign notifications for followers
        $this->info('Processing campaigns...');
        $campaigns = Campaign::with('charity')->where('status', 'published')->get();
        foreach ($campaigns as $campaign) {
            try {
                NotificationHelper::newCampaignFromFollowedCharity($campaign);
                $this->line("✓ Created notifications for campaign #{$campaign->id}");
            } catch (\Exception $e) {
                $this->error("✗ Failed for campaign #{$campaign->id}: " . $e->getMessage());
            }
        }

        // Generate follow notifications
        $this->info('Processing follows...');
        $follows = CharityFollow::with(['donor', 'charity'])->get();
        foreach ($follows as $follow) {
            try {
                if ($follow->charity && $follow->donor) {
                    NotificationHelper::charityFollowed($follow->charity, $follow->donor);
                }
                $this->line("✓ Created notification for follow #{$follow->id}");
            } catch (\Exception $e) {
                $this->error("✗ Failed for follow #{$follow->id}: " . $e->getMessage());
            }
        }

        // Generate comment notifications
        $this->info('Processing comments...');
        $comments = CampaignComment::with(['campaign.charity', 'user'])->get();
        foreach ($comments as $comment) {
            try {
                if ($comment->campaign && $comment->user) {
                    NotificationHelper::campaignCommented($comment->campaign, $comment->user);
                }
                $this->line("✓ Created notification for comment #{$comment->id}");
            } catch (\Exception $e) {
                $this->error("✗ Failed for comment #{$comment->id}: " . $e->getMessage());
            }
        }

        // Generate admin notifications for donations
        $this->info('Processing admin notifications for donations...');
        foreach ($donations as $donation) {
            try {
                NotificationHelper::newDonationAdmin($donation);
                $this->line("✓ Created admin notification for donation #{$donation->id}");
            } catch (\Exception $e) {
                $this->error("✗ Failed admin notification for donation #{$donation->id}: " . $e->getMessage());
            }
        }

        // Generate admin notifications for new users
        $this->info('Processing admin notifications for new users...');
        $donors = User::where('role', 'donor')->get();
        foreach ($donors as $donor) {
            try {
                NotificationHelper::newUserRegistration($donor);
                $this->line("✓ Created admin notification for user #{$donor->id}");
            } catch (\Exception $e) {
                $this->error("✗ Failed admin notification for user #{$donor->id}: " . $e->getMessage());
            }
        }

        // Generate admin notifications for charity registrations
        $this->info('Processing admin notifications for charities...');
        $charities = \App\Models\Charity::all();
        foreach ($charities as $charity) {
            try {
                NotificationHelper::newCharityRegistration($charity);
                $this->line("✓ Created admin notification for charity #{$charity->id}");
            } catch (\Exception $e) {
                $this->error("✗ Failed admin notification for charity #{$charity->id}: " . $e->getMessage());
            }
        }

        $totalNotifications = \App\Models\Notification::count();
        $this->info("✓ Complete! Total notifications created: {$totalNotifications}");

        return 0;
    }
}
