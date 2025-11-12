<?php

namespace App\Http\Controllers;

use App\Models\{Charity, Campaign, Donation};
use App\Helpers\ReportGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CharityCampaignAnalyticsController extends Controller
{
    /**
     * Export Campaign Analytics PDF for authenticated charity
     * Shows comprehensive campaign performance data
     */
    public function exportPDF(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            
            // Get charity for this user
            $charity = Charity::where('owner_id', $user->id)->first();
            
            if (!$charity) {
                return response()->json([
                    'error' => 'No charity found for your account'
                ], 404);
            }
            
            // Get date range (default to last 12 months)
            $startDate = $request->input('start_date', Carbon::now()->subYear()->startOfDay());
            $endDate = $request->input('end_date', Carbon::now()->endOfDay());
            
            if (is_string($startDate)) {
                $startDate = Carbon::parse($startDate);
            }
            if (is_string($endDate)) {
                $endDate = Carbon::parse($endDate);
            }
            
            // Get all campaigns for this charity
            // Using total_donations_received column for performance
            $campaigns = Campaign::where('charity_id', $charity->id)
                ->get();
            
            if ($campaigns->isEmpty()) {
                // Return a friendly PDF for charities with no campaigns
                $data = [
                    'charity' => [
                        'name' => $charity->name,
                        'reg_no' => $charity->reg_no,
                    ],
                    'period' => [
                        'start' => $startDate->format('F d, Y'),
                        'end' => $endDate->format('F d, Y'),
                    ],
                    'has_campaigns' => false,
                    'message' => 'No campaigns available for reporting. Start creating campaigns to track your performance!'
                ];
                
                $filename = 'campaign_analytics_' . str_replace(' ', '_', strtolower($charity->name)) . '_' . Carbon::now()->format('Y-m-d') . '.pdf';
                return ReportGenerator::generatePDF('reports.campaign-analytics', $data, $filename);
            }
            
            // Calculate summary metrics using the new columns
            $totalCampaigns = $campaigns->count();
            $totalDonations = $campaigns->sum('donors_count');
            $totalAmountRaised = $campaigns->sum('total_donations_received');
            
            // Format campaigns for table
            $campaignPerformance = $campaigns->map(function ($campaign) {
                $raised = $campaign->total_donations_received;
                $goal = $campaign->target_amount;
                $percentFunded = $goal > 0 ? ($raised / $goal) * 100 : 0;
                
                return [
                    'name' => $campaign->title,
                    'goal' => $goal,
                    'raised' => $raised,
                    'percent_funded' => min($percentFunded, 100),
                    'donor_count' => $campaign->donors_count,
                    'status' => $campaign->status,
                ];
            })->toArray();
            
            // Get top 3 performing campaigns
            $topCampaigns = collect($campaignPerformance)
                ->sortByDesc('raised')
                ->take(3)
                ->values()
                ->toArray();
            
            // Calculate milestone achievements
            $milestones = collect($campaignPerformance)->map(function ($campaign) {
                $percent = $campaign['percent_funded'];
                $achievements = [];
                
                if ($percent >= 100) {
                    $achievements[] = '100% Goal Achieved! 🎉';
                } elseif ($percent >= 75) {
                    $achievements[] = '75% Milestone Reached';
                } elseif ($percent >= 50) {
                    $achievements[] = '50% Milestone Reached';
                }
                
                return [
                    'name' => $campaign['name'],
                    'achievements' => $achievements,
                    'percent' => $percent
                ];
            })->filter(function ($item) {
                return !empty($item['achievements']);
            })->values()->toArray();
            
            // Get monthly donation trend
            $monthlyTrend = DB::table('donations')
                ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
                ->where('campaigns.charity_id', $charity->id)
                ->where('donations.status', 'completed')
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween('donations.donated_at', [$startDate, $endDate])
                          ->orWhereBetween('donations.created_at', [$startDate, $endDate]);
                })
                ->select(
                    DB::raw('DATE_FORMAT(COALESCE(donations.donated_at, donations.created_at), "%Y-%m") as month'),
                    DB::raw('SUM(donations.amount) as total'),
                    DB::raw('COUNT(donations.id) as count')
                )
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(function($item) {
                    return [
                        'month' => $item->month,
                        'total' => $item->total,
                        'count' => $item->count
                    ];
                })
                ->toArray();
            
            // Prepare comprehensive data for PDF
            $data = [
                'charity' => [
                    'name' => $charity->name,
                    'reg_no' => $charity->reg_no,
                ],
                'period' => [
                    'start' => $startDate->format('F d, Y'),
                    'end' => $endDate->format('F d, Y'),
                ],
                'has_campaigns' => true,
                'summary' => [
                    'total_campaigns' => $totalCampaigns,
                    'total_donations' => $totalDonations,
                    'total_amount_raised' => $totalAmountRaised,
                    'average_per_campaign' => $totalCampaigns > 0 ? $totalAmountRaised / $totalCampaigns : 0,
                ],
                'campaigns' => $campaignPerformance,
                'top_campaigns' => $topCampaigns,
                'milestones' => $milestones,
                'monthly_trend' => $monthlyTrend,
            ];
            
            // Generate filename: campaign_analytics_{charityname}_{date}.pdf
            $charityName = str_replace(' ', '_', strtolower($charity->name));
            $filename = 'campaign_analytics_' . $charityName . '_' . Carbon::now()->format('Y-m-d') . '.pdf';
            
            return ReportGenerator::generatePDF('reports.campaign-analytics', $data, $filename);
            
        } catch (\Exception $e) {
            \Log::error('Campaign Analytics Export Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'error' => 'Failed to generate campaign analytics',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
}
