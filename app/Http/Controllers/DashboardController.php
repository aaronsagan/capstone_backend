<?php

namespace App\Http\Controllers;

use App\Models\{User, Charity, Campaign, Donation};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Calculate average goal percentage across campaigns
     */
    private function calculateAverageGoalPercentage($campaigns)
    {
        $campaignsWithGoals = $campaigns->where('target_amount', '>', 0);
        
        if ($campaignsWithGoals->count() === 0) {
            return 0;
        }
        
        $totalPercent = $campaignsWithGoals->sum(function($campaign) {
            if ($campaign->target_amount > 0) {
                return min(($campaign->total_donations_received / $campaign->target_amount) * 100, 100);
            }
            return 0;
        });
        
        return round($totalPercent / $campaignsWithGoals->count(), 2);
    }
    
    /**
     * Get donor dashboard statistics
     */
    public function donorDashboard(Request $request)
    {
        $user = $request->user();
        
        // Get all donations for this donor
        $donations = Donation::where('donor_id', $user->id)->get();
        $completedDonations = $donations->where('status', 'completed')->where('is_refunded', false);
        
        // Calculate stats
        $stats = [
            'total_donated' => $completedDonations->sum('amount'),
            'charities_supported' => $donations->pluck('charity_id')->unique()->count(),
            'donations_made' => $donations->count(),
            'pending_donations' => $donations->where('status', 'pending')->count(),
            'completed_donations' => $completedDonations->count(),
            'first_donation_date' => $donations->min('donated_at'),
            'latest_donation_date' => $donations->max('donated_at'),
        ];
        
        // Get donation breakdown by charity
        $byCharity = DB::table('donations')
            ->join('charities', 'donations.charity_id', '=', 'charities.id')
            ->where('donations.donor_id', $user->id)
            ->where('donations.status', 'completed')
            ->where('donations.is_refunded', false)
            ->select('charities.id', 'charities.name', 'charities.logo_path', 
                     DB::raw('SUM(donations.amount) as total_amount'),
                     DB::raw('COUNT(donations.id) as donation_count'))
            ->groupBy('charities.id', 'charities.name', 'charities.logo_path')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get();
        
        // Get donation breakdown by campaign type
        $byCampaignType = DB::table('donations')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->where('donations.donor_id', $user->id)
            ->where('donations.status', 'completed')
            ->where('donations.is_refunded', false)
            ->select('campaigns.campaign_type', 
                     DB::raw('SUM(donations.amount) as total_amount'),
                     DB::raw('COUNT(donations.id) as donation_count'))
            ->groupBy('campaigns.campaign_type')
            ->orderByDesc('total_amount')
            ->get();
        
        // Get monthly donation trend (last 6 months)
        $monthlyTrend = DB::table('donations')
            ->where('donor_id', $user->id)
            ->where('status', 'completed')
            ->where('is_refunded', false)
            ->where('donated_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(donated_at, "%Y-%m") as month'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(id) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        return response()->json([
            'stats' => $stats,
            'by_charity' => $byCharity,
            'by_campaign_type' => $byCampaignType,
            'monthly_trend' => $monthlyTrend,
        ]);
    }
    
    /**
     * Get charity dashboard statistics
     */
    public function charityDashboard(Request $request)
    {
        $user = $request->user();
        
        // Get charity owned by this user
        $charity = Charity::where('owner_id', $user->id)->first();
        
        if (!$charity) {
            return response()->json(['message' => 'No charity found for this user'], 404);
        }
        
        // Get all campaigns for this charity
        $campaigns = Campaign::where('charity_id', $charity->id)->get();
        
        // Get all donations for this charity
        $donations = Donation::where('charity_id', $charity->id)->get();
        $completedDonations = $donations->where('status', 'completed')->where('is_refunded', false);
        
        // Calculate stats - using accurate calculations
        $stats = [
            'totalDonations' => round($completedDonations->sum('amount'), 2),
            'activeCampaigns' => $campaigns->where('status', 'published')->count(),
            'totalCampaigns' => $campaigns->count(),
            'pendingProofs' => $donations->where('status', 'pending')->count(),
            'verifiedDocuments' => $charity->documents()->where('status', 'approved')->count(),
            'totalDonors' => $donations->pluck('donor_id')->filter()->unique()->count(),
            'totalDonationsCount' => $completedDonations->count(),
            'pendingDonationsCount' => $donations->where('status', 'pending')->count(),
            'rejectedDonationsCount' => $donations->where('status', 'rejected')->count(),
            // NEW: Add average donation and goal percentage
            'averageDonation' => $completedDonations->count() > 0 
                ? round($completedDonations->sum('amount') / $completedDonations->count(), 2) 
                : 0,
            'averageGoalPercentage' => $this->calculateAverageGoalPercentage($campaigns),
        ];
        
        // Get donations over time (last 6 months)
        $donationsOverTime = DB::table('donations')
            ->where('charity_id', $charity->id)
            ->where('status', 'completed')
            ->where('is_refunded', false)
            ->where('donated_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(donated_at, "%Y-%m-%d") as date'),
                DB::raw('SUM(amount) as amount'),
                DB::raw('COUNT(id) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Get recent activities (last 10 donations)
        $recentActivities = Donation::where('charity_id', $charity->id)
            ->with(['donor:id,name', 'campaign:id,title'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($donation) {
                return [
                    'id' => $donation->id,
                    'description' => ($donation->donor ? $donation->donor->name : 'Anonymous') . 
                                   ' donated ₱' . number_format($donation->amount, 2) .
                                   ($donation->campaign ? ' to ' . $donation->campaign->title : ''),
                    'timestamp' => $donation->donated_at ?? $donation->created_at,
                    'status' => $donation->status,
                ];
            });
        
        // Get top campaigns by donations
        $topCampaigns = DB::table('donations')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->where('donations.charity_id', $charity->id)
            ->where('donations.status', 'completed')
            ->select('campaigns.id', 'campaigns.title', 'campaigns.cover_image_path',
                     DB::raw('SUM(donations.amount) as total_raised'),
                     DB::raw('COUNT(DISTINCT donations.donor_id) as donors_count'))
            ->groupBy('campaigns.id', 'campaigns.title', 'campaigns.cover_image_path')
            ->orderByDesc('total_raised')
            ->limit(5)
            ->get();
        
        return response()->json([
            'charity' => [
                'id' => $charity->id,
                'name' => $charity->name,
                'logo_path' => $charity->logo_path,
                'verification_status' => $charity->verification_status,
            ],
            'stats' => $stats,
            'donationsOverTime' => $donationsOverTime,
            'recentActivities' => $recentActivities,
            'topCampaigns' => $topCampaigns,
        ]);
    }
    
    /**
     * Get public platform statistics (for landing page)
     */
    public function publicStats()
    {
        $stats = [
            'total_charities' => Charity::where('verification_status', 'approved')->count(),
            'total_campaigns' => Campaign::where('status', 'published')->count(),
            'total_donors' => User::where('role', 'donor')
                ->whereHas('donations', function($q) {
                    $q->where('status', 'completed')->where('is_refunded', false);
                })->count(),
            'total_donations' => (float) Donation::where('status', 'completed')
                ->where('is_refunded', false)
                ->sum('amount'),
            'total_donation_count' => Donation::where('status', 'completed')
                ->where('is_refunded', false)
                ->count(),
            'lives_impacted' => Donation::where('status', 'completed')
                ->where('is_refunded', false)
                ->count() * 10, // Estimate 10 people impacted per donation
        ];
        
        return response()->json($stats);
    }
    
    /**
     * Get admin dashboard statistics
     */
    public function adminDashboard(Request $request)
    {
        $stats = [
            // Only count users who have verified their email
            'total_users' => User::whereNotNull('email_verified_at')->count(),
            'total_donors' => User::where('role', 'donor')->whereNotNull('email_verified_at')->count(),
            'total_charity_admins' => User::where('role', 'charity_admin')->whereNotNull('email_verified_at')->count(),
            'active_users' => User::where('status', 'active')->whereNotNull('email_verified_at')->count(),
            'suspended_users' => User::where('status', 'suspended')->whereNotNull('email_verified_at')->count(),
            
            'total_charities' => Charity::count(),
            'approved_charities' => Charity::where('verification_status', 'approved')->count(),
            'pending_charities' => Charity::where('verification_status', 'pending')->count(),
            'rejected_charities' => Charity::where('verification_status', 'rejected')->count(),
            
            'total_campaigns' => Campaign::count(),
            'active_campaigns' => Campaign::where('status', 'published')->count(),
            'draft_campaigns' => Campaign::where('status', 'draft')->count(),
            'closed_campaigns' => Campaign::where('status', 'closed')->count(),
            
            'total_donations' => Donation::count(),
            'completed_donations' => Donation::where('status', 'completed')->where('is_refunded', false)->count(),
            'pending_donations' => Donation::where('status', 'pending')->count(),
            'total_donated_amount' => Donation::where('status', 'completed')->where('is_refunded', false)->sum('amount'),
        ];
        
        // Get registration trend (last 6 months)
        $registrationTrend = DB::table('users')
            ->where('created_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(id) as count'),
                'role'
            )
            ->groupBy('month', 'role')
            ->orderBy('month')
            ->get();
        
        // Get donation trend (last 6 months)
        $donationTrend = DB::table('donations')
            ->where('status', 'completed')
            ->where('is_refunded', false)
            ->where('donated_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(donated_at, "%Y-%m") as month'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(id) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        // Get top 5 charities by donations
        $topCharities = DB::table('charities')
            ->join('campaigns', 'charities.id', '=', 'campaigns.charity_id')
            ->join('donations', 'campaigns.id', '=', 'donations.campaign_id')
            ->where('donations.status', 'completed')
            ->where('charities.verification_status', 'approved')
            ->select(
                'charities.id',
                'charities.name',
                DB::raw('SUM(donations.amount) as total_raised'),
                DB::raw('COUNT(DISTINCT donations.id) as donation_count'),
                DB::raw('COUNT(DISTINCT campaigns.id) as campaign_count')
            )
            ->groupBy('charities.id', 'charities.name')
            ->orderBy('total_raised', 'desc')
            ->limit(5)
            ->get();
        
        // Get recent users (last 10)
        $recentUsers = User::select('id', 'name', 'email', 'role', 'status', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get pending charities
        $pendingCharities = Charity::with('owner:id,name,email')
            ->where('verification_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get system notifications count
        $notifications = [
            'pending_charities' => Charity::where('verification_status', 'pending')->count(),
            'pending_reports' => DB::table('reports')->where('status', 'pending')->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            'expiring_documents' => DB::table('charity_documents')
                ->where('expiry_date', '<=', now()->addDays(30))
                ->where('expiry_date', '>=', now())
                ->count(),
        ];
        
        return response()->json([
            'stats' => $stats,
            'registrationTrend' => $registrationTrend,
            'donationTrend' => $donationTrend,
            'topCharities' => $topCharities,
            'recentUsers' => $recentUsers,
            'pendingCharities' => $pendingCharities,
            'notifications' => $notifications,
        ]);
    }
}
