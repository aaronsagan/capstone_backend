<?php

namespace App\Http\Controllers;

use App\Models\{Campaign, Donation, Charity};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Cache};
use Carbon\Carbon;

/**
 * Donor Analytics Controller
 * Provides site-wide campaign analytics for donor-facing insights
 * All data is aggregated across charities (public analytics)
 */
class DonorAnalyticsController extends Controller
{
    /**
     * GET /api/donor-analytics/summary
     * Returns high-level metrics for the dashboard
     */
    public function summary(Request $request)
    {
        $filters = $this->parseFilters($request);
        $cacheKey = "donor_analytics_summary_" . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($filters) {
            $query = $this->buildBaseQuery($filters);
            
            $totalCampaigns = (clone $query)->distinct('campaigns.id')->count('campaigns.id');
            $activeCampaigns = (clone $query)->where('campaigns.status', 'published')->distinct('campaigns.id')->count('campaigns.id');
            
            $donationStats = Donation::where('status', 'completed')
                ->where('is_refunded', false)
                ->when($filters['date_from'], fn($q) => $q->whereRaw('COALESCE(donated_at, created_at) >= ?', [$filters['date_from']]))
                ->when($filters['date_to'], fn($q) => $q->whereRaw('COALESCE(donated_at, created_at) <= ?', [$filters['date_to']]))
                ->when($filters['campaign_ids'], fn($q) => $q->whereIn('campaign_id', $filters['campaign_ids']))
                ->selectRaw('SUM(amount) as total, AVG(amount) as average, COUNT(*) as count')
                ->first();
            
            return response()->json([
                'total_campaigns' => $totalCampaigns,
                'active_campaigns' => $activeCampaigns,
                'total_donations_amount' => (float) ($donationStats->total ?? 0),
                'avg_donation' => (float) ($donationStats->average ?? 0),
                'total_donations_count' => (int) ($donationStats->count ?? 0),
            ]);
        });
    }
    
    /**
     * POST /api/donor-analytics/query
     * Complex analytics query with filters
     */
    public function query(Request $request)
    {
        $filters = $this->parseFilters($request);
        $cacheKey = "donor_analytics_query_" . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, 180, function () use ($filters) {
            return response()->json([
                'campaign_type_distribution' => $this->getCampaignTypeDistribution($filters),
                'top_trending_campaigns' => $this->getTopTrendingCampaigns($filters),
                'charity_rankings' => $this->getCharityRankings($filters),
                'donations_time_series' => $this->getDonationsTimeSeries($filters),
                'campaign_frequency_time_series' => $this->getCampaignFrequencyTimeSeries($filters),
                'location_distribution' => $this->getLocationDistribution($filters),
                'beneficiary_breakdown' => $this->getBeneficiaryBreakdown($filters),
            ]);
        });
    }
    
    /**
     * GET /api/donor-analytics/campaign/{id}
     * Detailed analytics for a single campaign (drill-down)
     */
    public function campaignDetails($id)
    {
        $cacheKey = "donor_analytics_campaign_{$id}";
        
        return Cache::remember($cacheKey, 300, function () use ($id) {
            $campaign = Campaign::with(['charity'])->findOrFail($id);
            
            $donations = Donation::where('campaign_id', $id)
                ->where('status', 'completed')
                ->where('is_refunded', false)
                ->selectRaw('
                    COUNT(*) as count,
                    SUM(amount) as total,
                    AVG(amount) as average,
                    MIN(amount) as min,
                    MAX(amount) as max
                ')
                ->first();
            
            $timelineSeries = Donation::where('campaign_id', $id)
                ->where('status', 'completed')
                ->where('is_refunded', false)
                ->selectRaw('DATE(COALESCE(donated_at, created_at)) as date, COUNT(*) as count, SUM(amount) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
            
            return response()->json([
                'campaign' => [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'type' => $campaign->campaign_type,
                    'charity' => $campaign->charity->name ?? 'Unknown',
                    'target_amount' => (float) $campaign->target_amount,
                    'current_amount' => (float) ($donations->total ?? 0),
                    'status' => $campaign->status,
                    'location' => [
                        'region' => $campaign->region,
                        'province' => $campaign->province,
                        'city' => $campaign->city,
                        'barangay' => $campaign->barangay,
                    ],
                ],
                'donation_stats' => [
                    'count' => (int) $donations->count,
                    'total' => (float) $donations->total,
                    'average' => (float) $donations->average,
                    'min' => (float) $donations->min,
                    'max' => (float) $donations->max,
                ],
                'timeline' => $timelineSeries->map(fn($item) => [
                    'date' => $item->date,
                    'count' => (int) $item->count,
                    'total' => (float) $item->total,
                ]),
                'why_trending' => $this->generateWhyTrending($campaign, $donations),
            ]);
        });
    }
    
    /**
     * GET /api/donor-analytics/donor/{id}/overview (authenticated)
     * Personal giving analytics for logged-in donor
     */
    public function donorOverview($id, Request $request)
    {
        // Authorization check
        if (!$request->user() || $request->user()->id != $id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $donations = Donation::where('donor_id', $id)
            ->where('status', 'completed')
            ->where('is_refunded', false)
            ->selectRaw('COUNT(*) as count, SUM(amount) as total, AVG(amount) as average')
            ->first();
        
        $recentTrend = Donation::where('donor_id', $id)
            ->where('status', 'completed')
            ->where('is_refunded', false)
            ->whereRaw('COALESCE(donated_at, created_at) >= ?', [now()->subDays(30)])
            ->selectRaw('DATE(COALESCE(donated_at, created_at)) as date, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return response()->json([
            'total_donated' => (float) $donations->total,
            'total_count' => (int) $donations->count,
            'average_donation' => (float) $donations->average,
            'recent_trend' => $recentTrend->map(fn($item) => [
                'date' => $item->date,
                'count' => (int) $item->count,
                'total' => (float) $item->total,
            ]),
        ]);
    }
    
    // ========== PRIVATE HELPER METHODS ==========
    
    private function parseFilters(Request $request)
    {
        return [
            'date_from' => $request->input('date_from') ? Carbon::parse($request->input('date_from')) : null,
            'date_to' => $request->input('date_to') ? Carbon::parse($request->input('date_to')) : null,
            'campaign_types' => $request->input('campaign_types', []),
            'charity_ids' => $request->input('charity_ids', []),
            'region' => $request->input('region'),
            'province' => $request->input('province'),
            'city' => $request->input('city'),
            'barangay' => $request->input('barangay'),
            'beneficiary_ids' => $request->input('beneficiary_ids', []),
            'granularity' => $request->input('granularity', 'daily'), // daily, weekly, monthly
            'campaign_ids' => $request->input('campaign_ids', []),
        ];
    }
    
    private function buildBaseQuery($filters)
    {
        $query = Campaign::query()
            ->where('status', '!=', 'archived');
        
        if ($filters['date_from']) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['campaign_types'])) {
            $query->whereIn('campaign_type', $filters['campaign_types']);
        }
        if (!empty($filters['charity_ids'])) {
            $query->whereIn('charity_id', $filters['charity_ids']);
        }
        if ($filters['region']) {
            $query->where('region', $filters['region']);
        }
        if ($filters['province']) {
            $query->where('province', $filters['province']);
        }
        if ($filters['city']) {
            $query->where('city', $filters['city']);
        }
        if ($filters['barangay']) {
            $query->where('barangay', $filters['barangay']);
        }
        if (!empty($filters['beneficiary_ids'])) {
            $query->whereHas('beneficiaries', fn($q) => $q->whereIn('beneficiaries.id', $filters['beneficiary_ids']));
        }
        
        return $query;
    }
    
    private function getCampaignTypeDistribution($filters)
    {
        $query = $this->buildBaseQuery($filters);
        
        return $query->select('campaign_type', DB::raw('COUNT(*) as count'))
            ->whereNotNull('campaign_type')
            ->groupBy('campaign_type')
            ->orderBy('count', 'desc')
            ->get()
            ->map(fn($item) => [
                'type' => $item->campaign_type,
                'label' => ucwords(str_replace('_', ' ', $item->campaign_type)),
                'count' => (int) $item->count,
                'total_raised' => 0, // Could calculate from donations if needed
                'percentage' => 0, // Will be calculated in frontend
            ])
            ->toArray();
    }
    
    private function getTopTrendingCampaigns($filters)
    {
        $days = $filters['date_to'] && $filters['date_from'] 
            ? $filters['date_to']->diffInDays($filters['date_from'])
            : 30;
        
        return Campaign::select(
                'campaigns.id',
                'campaigns.title',
                'campaigns.campaign_type',
                'campaigns.target_amount',
                'campaigns.charity_id',
                DB::raw('SUM(CASE WHEN donations.status = "completed" AND donations.is_refunded = 0 THEN 1 ELSE 0 END) as donation_count'),
                DB::raw('SUM(CASE WHEN donations.status = "completed" AND donations.is_refunded = 0 THEN donations.amount ELSE 0 END) as total_amount'),
                DB::raw('AVG(CASE WHEN donations.status = "completed" AND donations.is_refunded = 0 THEN donations.amount ELSE NULL END) as avg_amount')
            )
            ->leftJoin('donations', function ($join) use ($days) {
                $join->on('campaigns.id', '=', 'donations.campaign_id')
                    ->whereRaw('COALESCE(donated_at, donations.created_at) >= ?', [now()->subDays($days)]);
            })
            ->where('campaigns.status', 'published')
            ->when(!empty($filters['campaign_types']), fn($q) => $q->whereIn('campaigns.campaign_type', $filters['campaign_types']))
            ->when(!empty($filters['charity_ids']), fn($q) => $q->whereIn('campaigns.charity_id', $filters['charity_ids']))
            ->groupBy('campaigns.id', 'campaigns.title', 'campaigns.campaign_type', 'campaigns.target_amount', 'campaigns.charity_id')
            ->having('donation_count', '>', 0)
            ->orderBy('donation_count', 'desc')
            ->orderBy('total_amount', 'desc')
            ->limit(10)
            ->with('charity:id,name')
            ->get()
            ->map(fn($campaign) => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'type' => $campaign->campaign_type,
                'charity' => $campaign->charity->name ?? 'Unknown',
                'target_amount' => (float) $campaign->target_amount,
                'current_amount' => (float) $campaign->total_amount,
                'donation_count' => (int) $campaign->donation_count,
                'total_amount' => (float) $campaign->total_amount,
                'avg_amount' => (float) $campaign->avg_amount,
                'progress' => $campaign->target_amount > 0 
                    ? round(($campaign->total_amount / $campaign->target_amount) * 100, 2)
                    : 0,
            ])
            ->toArray();
    }
    
    private function getCharityRankings($filters)
    {
        $query = Charity::select(
                'charities.id',
                'charities.name',
                DB::raw('COUNT(DISTINCT campaigns.id) as campaign_count'),
                DB::raw('COALESCE(SUM(donations.amount), 0) as total_raised'),
                DB::raw('COUNT(DISTINCT donations.id) as donation_count')
            )
            ->leftJoin('campaigns', 'charities.id', '=', 'campaigns.charity_id')
            ->leftJoin('donations', function ($join) {
                $join->on('campaigns.id', '=', 'donations.campaign_id')
                    ->where('donations.status', '=', 'completed')
                    ->where('donations.is_refunded', '=', 0);
            })
            ->where('charities.verification_status', 'approved');
        
        if ($filters['date_from']) {
            $query->where('campaigns.created_at', '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $query->where('campaigns.created_at', '<=', $filters['date_to']);
        }
        
        return $query->groupBy('charities.id', 'charities.name')
            ->having('campaign_count', '>', 0)
            ->orderBy('total_raised', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($charity) => [
                'id' => $charity->id,
                'name' => $charity->name,
                'campaign_count' => (int) $charity->campaign_count,
                'total_raised' => (float) $charity->total_raised,
                'donation_count' => (int) $charity->donation_count,
            ])
            ->toArray();
    }
    
    private function getDonationsTimeSeries($filters)
    {
        $granularity = $filters['granularity'];
        $dateFormat = match($granularity) {
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };
        
        $query = Donation::select(
                DB::raw("DATE_FORMAT(COALESCE(donated_at, created_at), '{$dateFormat}') as period"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total'),
                DB::raw('AVG(amount) as average')
            )
            ->where('status', 'completed')
            ->where('is_refunded', false);
        
        if ($filters['date_from']) {
            $query->whereRaw('COALESCE(donated_at, created_at) >= ?', [$filters['date_from']]);
        }
        if ($filters['date_to']) {
            $query->whereRaw('COALESCE(donated_at, created_at) <= ?', [$filters['date_to']]);
        }
        if (!empty($filters['campaign_ids'])) {
            $query->whereIn('campaign_id', $filters['campaign_ids']);
        }
        
        return $query->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn($item) => [
                'period' => $item->period,
                'count' => (int) $item->count,
                'total' => (float) $item->total,
                'average' => (float) $item->average,
            ])
            ->toArray();
    }
    
    private function getCampaignFrequencyTimeSeries($filters)
    {
        $granularity = $filters['granularity'];
        $dateFormat = match($granularity) {
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };
        
        $query = $this->buildBaseQuery($filters);
        
        return $query->select(
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                'campaign_type',
                DB::raw('COUNT(*) as count')
            )
            ->whereNotNull('campaign_type')
            ->groupBy('period', 'campaign_type')
            ->orderBy('period')
            ->get()
            ->groupBy('period')
            ->map(fn($group, $period) => [
                'period' => $period,
                'types' => $group->mapWithKeys(fn($item) => [
                    $item->campaign_type => (int) $item->count
                ])->toArray(),
                'total' => $group->sum('count'),
            ])
            ->values()
            ->toArray();
    }
    
    private function getLocationDistribution($filters)
    {
        $query = $this->buildBaseQuery($filters);
        
        // Get all levels of location data
        $regions = (clone $query)->select('region', DB::raw('COUNT(*) as campaign_count, 0 as total_raised'))
            ->whereNotNull('region')
            ->groupBy('region')
            ->orderBy('campaign_count', 'desc')
            ->get()
            ->map(fn($item) => [
                'region' => $item->region,
                'campaign_count' => (int) $item->campaign_count,
                'total_raised' => (float) $item->total_raised,
                'avg_raised' => $item->campaign_count > 0 ? round($item->total_raised / $item->campaign_count, 2) : 0,
            ]);
        
        $provinces = (clone $query)->select('region', 'province', DB::raw('COUNT(*) as campaign_count, 0 as total_raised'))
            ->whereNotNull('province')
            ->groupBy('region', 'province')
            ->orderBy('campaign_count', 'desc')
            ->limit(20)
            ->get()
            ->map(fn($item) => [
                'region' => $item->region,
                'province' => $item->province,
                'campaign_count' => (int) $item->campaign_count,
                'total_raised' => (float) $item->total_raised,
                'avg_raised' => $item->campaign_count > 0 ? round($item->total_raised / $item->campaign_count, 2) : 0,
            ]);
        
        $cities = (clone $query)->select('region', 'province', 'city', DB::raw('COUNT(*) as campaign_count, 0 as total_raised'))
            ->whereNotNull('city')
            ->groupBy('region', 'province', 'city')
            ->orderBy('campaign_count', 'desc')
            ->limit(20)
            ->get()
            ->map(fn($item) => [
                'region' => $item->region,
                'province' => $item->province,
                'city' => $item->city,
                'campaign_count' => (int) $item->campaign_count,
                'total_raised' => (float) $item->total_raised,
                'avg_raised' => $item->campaign_count > 0 ? round($item->total_raised / $item->campaign_count, 2) : 0,
            ]);
        
        return [
            'regions' => $regions->toArray(),
            'provinces' => $provinces->toArray(),
            'cities' => $cities->toArray(),
        ];
    }
    
    private function getBeneficiaryBreakdown($filters)
    {
        // Return empty if beneficiary model/table doesn't exist
        if (!class_exists('\App\Models\Beneficiary')) {
            return [];
        }
        
        try {
            $query = Campaign::select(
                    'beneficiaries.id',
                    'beneficiaries.name',
                    DB::raw('COUNT(DISTINCT campaigns.id) as campaign_count'),
                    DB::raw('SUM(campaigns.current_amount) as total_raised')
                )
                ->join('campaign_beneficiary', 'campaigns.id', '=', 'campaign_beneficiary.campaign_id')
                ->join('beneficiaries', 'campaign_beneficiary.beneficiary_id', '=', 'beneficiaries.id')
                ->where('campaigns.status', '!=', 'archived');
            
            if ($filters['date_from']) {
                $query->where('campaigns.created_at', '>=', $filters['date_from']);
            }
            if ($filters['date_to']) {
                $query->where('campaigns.created_at', '<=', $filters['date_to']);
            }
            if (!empty($filters['campaign_types'])) {
                $query->whereIn('campaigns.campaign_type', $filters['campaign_types']);
            }
            
            return $query->groupBy('beneficiaries.id', 'beneficiaries.name')
                ->orderBy('campaign_count', 'desc')
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'campaign_count' => (int) $item->campaign_count,
                    'total_raised' => (float) $item->total_raised,
                    'avg_raised' => $item->campaign_count > 0 ? round($item->total_raised / $item->campaign_count, 2) : 0,
                ])
                ->toArray();
        } catch (\Exception $e) {
            // Return empty if table doesn't exist
            return [];
        }
    }
    
    private function generateWhyTrending($campaign, $donations)
    {
        $reasons = [];
        
        if ($donations->count > 10) {
            $reasons[] = "High donation volume with {$donations->count} contributions";
        }
        if ($donations->average > 1000) {
            $reasons[] = "Strong donor commitment with ₱" . number_format($donations->average, 0) . " average donation";
        }
        if ($campaign->current_amount / max($campaign->target_amount, 1) > 0.5) {
            $reasons[] = "Over 50% funded showing community trust";
        }
        
        return empty($reasons) ? ['Active campaign with recent support'] : $reasons;
    }
}
