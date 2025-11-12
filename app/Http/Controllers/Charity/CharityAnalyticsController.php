<?php

namespace App\Http\Controllers\Charity;

use App\Http\Controllers\Controller;
use App\Models\{Charity, Campaign, Donation};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CharityAnalyticsController extends Controller
{
    /**
     * Get charity analytics dashboard data
     * Fixes the zero values issue by properly calculating all metrics
     */
    public function getDashboardAnalytics(Request $request)
    {
        $user = $request->user();
        
        // Get charity
        $charity = Charity::where('owner_id', $user->id)->first();
        
        if (!$charity) {
            return response()->json(['message' => 'No charity found'], 404);
        }

        // Get all campaigns with proper donation totals
        $campaigns = Campaign::where('charity_id', $charity->id)->get();
        
        // Get all donations
        $allDonations = Donation::where('charity_id', $charity->id)->get();
        $completedDonations = $allDonations->where('status', 'completed');
        
        // Calculate metrics
        $totalCampaigns = $campaigns->count();
        $verifiedDonations = $completedDonations->count();
        $totalRaised = $completedDonations->sum('amount');
        $avgDonation = $verifiedDonations > 0 ? $totalRaised / $verifiedDonations : 0;
        
        // Calculate average goal percentage across all campaigns
        $campaignsWithGoals = $campaigns->where('target_amount', '>', 0);
        $avgGoalPercent = 0;
        
        if ($campaignsWithGoals->count() > 0) {
            $totalPercent = $campaignsWithGoals->sum(function($campaign) {
                if ($campaign->target_amount > 0) {
                    return min(($campaign->total_donations_received / $campaign->target_amount) * 100, 100);
                }
                return 0;
            });
            $avgGoalPercent = $totalPercent / $campaignsWithGoals->count();
        }
        
        return response()->json([
            'summary' => [
                'total_campaigns' => $totalCampaigns,
                'verified_donations' => $verifiedDonations,
                'total_raised' => round($totalRaised, 2),
                'avg_donation' => round($avgDonation, 2),
                'avg_goal_percent' => round($avgGoalPercent, 2),
                'pending_donations' => $allDonations->where('status', 'pending')->count(),
                'unique_donors' => $completedDonations->pluck('donor_id')->filter()->unique()->count(),
            ],
            'campaigns_breakdown' => [
                'published' => $campaigns->where('status', 'published')->count(),
                'draft' => $campaigns->where('status', 'draft')->count(),
                'closed' => $campaigns->where('status', 'closed')->count(),
                'archived' => $campaigns->where('status', 'archived')->count(),
            ],
        ]);
    }

    /**
     * Get donation reports with proper calculations
     */
    public function getDonationReports(Request $request)
    {
        $user = $request->user();
        $charity = Charity::where('owner_id', $user->id)->first();
        
        if (!$charity) {
            return response()->json(['message' => 'No charity found'], 404);
        }

        $days = $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days);
        
        // Get donations in date range
        $donations = Donation::where('charity_id', $charity->id)
            ->where('created_at', '>=', $startDate)
            ->get();
        
        $completed = $donations->where('status', 'completed');
        
        // Daily breakdown
        $daily = $completed->groupBy(function($donation) {
            return Carbon::parse($donation->donated_at ?? $donation->created_at)->format('Y-m-d');
        })->map(function($dayDonations, $date) {
            return [
                'date' => $date,
                'count' => $dayDonations->count(),
                'amount' => round($dayDonations->sum('amount'), 2),
            ];
        })->values();
        
        // By campaign
        $byCampaign = $completed->groupBy('campaign_id')->map(function($campaignDonations, $campaignId) {
            $campaign = Campaign::find($campaignId);
            return [
                'campaign_id' => $campaignId,
                'campaign_name' => $campaign ? $campaign->title : 'Unknown',
                'count' => $campaignDonations->count(),
                'amount' => round($campaignDonations->sum('amount'), 2),
            ];
        })->values();
        
        return response()->json([
            'period_days' => $days,
            'summary' => [
                'total_donations' => $completed->count(),
                'total_amount' => round($completed->sum('amount'), 2),
                'pending_donations' => $donations->where('status', 'pending')->count(),
                'average_donation' => $completed->count() > 0 ? round($completed->sum('amount') / $completed->count(), 2) : 0,
            ],
            'daily_breakdown' => $daily->sortBy('date')->values(),
            'by_campaign' => $byCampaign->sortByDesc('amount')->values(),
        ]);
    }

    /**
     * Get campaign analytics with overfunded campaigns analysis
     */
    public function getCampaignAnalytics(Request $request)
    {
        $user = $request->user();
        $charity = Charity::where('owner_id', $user->id)->first();
        
        if (!$charity) {
            return response()->json(['message' => 'No charity found'], 404);
        }

        $campaigns = Campaign::where('charity_id', $charity->id)->get();
        
        // Regular campaign stats
        $campaignStats = $campaigns->map(function($campaign) {
            $fundingPercent = 0;
            if ($campaign->target_amount > 0) {
                $fundingPercent = ($campaign->total_donations_received / $campaign->target_amount) * 100;
            }
            
            return [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'status' => $campaign->status,
                'target_amount' => round($campaign->target_amount, 2),
                'total_received' => round($campaign->total_donations_received, 2),
                'funding_percent' => round($fundingPercent, 2),
                'donors_count' => $campaign->donors_count,
                'is_overfunded' => $fundingPercent >= 100,
            ];
        });
        
        // Overfunded campaigns analysis
        $overfundedCampaigns = $this->getOverfundedCampaignsAnalysis($charity->id);
        
        return response()->json([
            'campaigns' => $campaignStats,
            'summary' => [
                'total_campaigns' => $campaigns->count(),
                'active_campaigns' => $campaigns->where('status', 'published')->count(),
                'completed_campaigns' => $campaigns->where('status', 'closed')->count(),
                'total_raised' => round($campaigns->sum('total_donations_received'), 2),
                'overfunded_count' => $overfundedCampaigns['summary']['count'],
            ],
            'overfunded_campaigns' => $overfundedCampaigns,
        ]);
    }

    /**
     * Get detailed overfunded campaigns analysis
     * Shows campaigns that reached 100% but still receiving donations
     */
    public function getOverfundedCampaignsAnalysis($charityId)
    {
        $campaigns = Campaign::where('charity_id', $charityId)
            ->where('target_amount', '>', 0)
            ->whereRaw('total_donations_received >= target_amount')
            ->with('donations')
            ->get();
        
        $analysis = $campaigns->map(function($campaign) {
            $targetAmount = $campaign->target_amount;
            $totalReceived = $campaign->total_donations_received;
            $excessAmount = $totalReceived - $targetAmount;
            $fundingPercent = ($totalReceived / $targetAmount) * 100;
            $additionalPercent = $fundingPercent - 100;
            
            // Find when 100% was reached
            $donations = $campaign->donations()
                ->where('status', 'completed')
                ->orderBy('donated_at', 'asc')
                ->orderBy('created_at', 'asc')
                ->get();
            
            $runningTotal = 0;
            $dateReached100 = null;
            $donationsAfter100 = 0;
            $amountAfter100 = 0;
            
            foreach ($donations as $donation) {
                $runningTotal += $donation->amount;
                
                if ($dateReached100 === null && $runningTotal >= $targetAmount) {
                    $dateReached100 = $donation->donated_at ?? $donation->created_at;
                } elseif ($dateReached100 !== null) {
                    $donationsAfter100++;
                    $amountAfter100 += $donation->amount;
                }
            }
            
            $daysOver100 = $dateReached100 
                ? Carbon::parse($dateReached100)->diffInDays(Carbon::now())
                : 0;
            
            return [
                'campaign_id' => $campaign->id,
                'title' => $campaign->title,
                'status' => $campaign->status,
                'target_amount' => round($targetAmount, 2),
                'total_received' => round($totalReceived, 2),
                'excess_amount' => round($excessAmount, 2),
                'funding_percent' => round($fundingPercent, 2),
                'additional_percent_over_100' => round($additionalPercent, 2),
                'date_reached_100' => $dateReached100 ? Carbon::parse($dateReached100)->toDateTimeString() : null,
                'days_since_100' => $daysOver100,
                'donations_after_100' => $donationsAfter100,
                'amount_after_100' => round($amountAfter100, 2),
                'total_donors' => $campaign->donors_count,
            ];
        })->sortByDesc('additional_percent_over_100')->values();
        
        return [
            'summary' => [
                'count' => $analysis->count(),
                'total_excess_amount' => round($analysis->sum('excess_amount'), 2),
                'total_donations_after_100' => $analysis->sum('donations_after_100'),
                'total_amount_after_100' => round($analysis->sum('amount_after_100'), 2),
                'average_overfunding_percent' => $analysis->count() > 0 
                    ? round($analysis->avg('additional_percent_over_100'), 2)
                    : 0,
            ],
            'campaigns' => $analysis,
        ];
    }

    /**
     * Get detailed timeline for specific overfunded campaign
     */
    public function getOverfundedCampaignTimeline(Request $request, $campaignId)
    {
        $user = $request->user();
        $charity = Charity::where('owner_id', $user->id)->first();
        
        if (!$charity) {
            return response()->json(['message' => 'No charity found'], 404);
        }

        $campaign = Campaign::where('id', $campaignId)
            ->where('charity_id', $charity->id)
            ->first();
        
        if (!$campaign) {
            return response()->json(['message' => 'Campaign not found'], 404);
        }

        $targetAmount = $campaign->target_amount;
        
        if ($targetAmount <= 0) {
            return response()->json(['message' => 'Campaign has no target amount'], 400);
        }

        // Get all completed donations in chronological order
        $donations = Donation::where('campaign_id', $campaignId)
            ->where('status', 'completed')
            ->orderBy('donated_at', 'asc')
            ->orderBy('created_at', 'asc')
            ->with('donor:id,name')
            ->get();
        
        $runningTotal = 0;
        $reached100 = false;
        $dateReached100 = null;
        
        $timeline = $donations->map(function($donation) use (&$runningTotal, $targetAmount, &$reached100, &$dateReached100) {
            $runningTotal += $donation->amount;
            $progressPercent = ($runningTotal / $targetAmount) * 100;
            
            $milestone = null;
            if (!$reached100 && $runningTotal >= $targetAmount) {
                $milestone = '100% Funded!';
                $reached100 = true;
                $dateReached100 = $donation->donated_at ?? $donation->created_at;
            }
            
            return [
                'donation_id' => $donation->id,
                'donor_name' => $donation->is_anonymous ? 'Anonymous' : ($donation->donor->name ?? 'Anonymous'),
                'amount' => round($donation->amount, 2),
                'running_total' => round($runningTotal, 2),
                'progress_percent' => round($progressPercent, 2),
                'donated_at' => ($donation->donated_at ?? $donation->created_at)->toDateTimeString(),
                'is_after_100' => $reached100 && $milestone === null,
                'milestone' => $milestone,
            ];
        });
        
        return response()->json([
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'target_amount' => round($targetAmount, 2),
                'total_received' => round($campaign->total_donations_received, 2),
                'funding_percent' => round(($campaign->total_donations_received / $targetAmount) * 100, 2),
                'status' => $campaign->status,
            ],
            'timeline' => $timeline,
            'date_reached_100' => $dateReached100 ? Carbon::parse($dateReached100)->toDateTimeString() : null,
            'total_donations' => $donations->count(),
        ]);
    }

    /**
     * Get overfunded campaigns list for clickable view
     */
    public function getOverfundedCampaignsList(Request $request)
    {
        $user = $request->user();
        $charity = Charity::where('owner_id', $user->id)->first();
        
        if (!$charity) {
            return response()->json(['message' => 'No charity found'], 404);
        }

        return response()->json($this->getOverfundedCampaignsAnalysis($charity->id));
    }
}
