<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\Charity;
use App\Models\Campaign;
use App\Models\User;
use App\Models\FundUsageLog;
use App\Models\RefundRequest;
use App\Models\RecurringDonation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FundTrackingController extends Controller
{
    /**
     * Get fund tracking statistics
     */
    public function getStatistics(Request $request)
    {
        $days = $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days);
        $previousStartDate = Carbon::now()->subDays($days * 2);
        $previousEndDate = $startDate->copy();

        // Inflows: completed donations within period (excluding refunded)
        $confirmedDonations = Donation::where('status', 'completed')
            ->where('is_refunded', false)
            ->whereRaw('COALESCE(donated_at, created_at) >= ?', [$startDate])
            ->get();

        // Outflows: fund usage logs (expenses/disbursements) within period
        $fundUsages = FundUsageLog::where('spent_at', '>=', $startDate)->get();

        // For transaction count, include ALL donation records (any status) + all fund usage logs
        $allDonations = Donation::whereRaw('COALESCE(donated_at, created_at) >= ?', [$startDate])->get();

        $totalDonations = $confirmedDonations->sum('amount');
        $totalDisbursements = $fundUsages->sum('amount');
        $netFlow = $totalDonations - $totalDisbursements;
        $transactionCount = $allDonations->count() + $fundUsages->count();

        // Previous period for growth calculation (excluding refunded)
        $previousDonations = Donation::where('status', 'completed')
            ->where('is_refunded', false)
            ->whereRaw('COALESCE(donated_at, created_at) >= ?', [$previousStartDate])
            ->whereRaw('COALESCE(donated_at, created_at) < ?', [$previousEndDate])
            ->sum('amount');
        
        $donationGrowth = $previousDonations > 0 
            ? (($totalDonations - $previousDonations) / $previousDonations) * 100 
            : 0;

        // Donor statistics (excluding refunded donations)
        $uniqueDonors = Donation::where('status', 'completed')
            ->where('is_refunded', false)
            ->whereRaw('COALESCE(donated_at, created_at) >= ?', [$startDate])
            ->whereNotNull('donor_id')
            ->distinct('donor_id')
            ->count('donor_id');

        $newDonors = User::where('role', 'donor')
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('email_verified_at')
            ->count();

        $averageDonation = $confirmedDonations->count() > 0 
            ? $totalDonations / $confirmedDonations->count() 
            : 0;

        // Charity statistics (excluding refunded donations)
        $activeCharities = Donation::where('status', 'completed')
            ->where('is_refunded', false)
            ->whereRaw('COALESCE(donated_at, created_at) >= ?', [$startDate])
            ->distinct('charity_id')
            ->count('charity_id');

        // Refund statistics
        $refundRequests = RefundRequest::where('created_at', '>=', $startDate)->get();
        $totalRefunds = $refundRequests->where('status', 'approved')->sum('refund_amount');
        $pendingRefunds = $refundRequests->where('status', 'pending')->count();

        // Recurring donation statistics
        $activeRecurring = RecurringDonation::where('status', 'active')->count();
        $recurringRevenue = RecurringDonation::where('status', 'active')
            ->sum('total_amount');

        // Donation status breakdown
        $pendingDonations = $allDonations->where('status', 'pending')->sum('amount');
        $completedDonations = $allDonations->where('status', 'completed')->count();
        $rejectedDonations = $allDonations->where('status', 'rejected')->count();

        return response()->json([
            // Financial metrics
            'total_donations' => $totalDonations,
            'total_disbursements' => $totalDisbursements,
            'net_flow' => $netFlow,
            'transaction_count' => $transactionCount,
            'average_donation' => round($averageDonation, 2),
            'pending_amount' => $pendingDonations,
            'donation_growth' => round($donationGrowth, 2),
            
            // Donor metrics
            'unique_donors' => $uniqueDonors,
            'new_donors' => $newDonors,
            'active_charities' => $activeCharities,
            
            // Donation status
            'completed_donations_count' => $completedDonations,
            'rejected_donations_count' => $rejectedDonations,
            'pending_donations_count' => $allDonations->where('status', 'pending')->count(),
            
            // Refunds
            'total_refunds' => $totalRefunds,
            'pending_refunds_count' => $pendingRefunds,
            'refund_requests_count' => $refundRequests->count(),
            
            // Recurring donations
            'active_recurring_donations' => $activeRecurring,
            'recurring_revenue' => $recurringRevenue,
            
            'period_days' => $days,
        ]);
    }

    /**
     * Get all transactions (donations and disbursements)
     */
    public function getTransactions(Request $request)
    {
        try {
            $days = $request->input('days', 30);
            $startDate = Carbon::now()->subDays($days);

        // Fetch ALL donations (including pending, confirmed, rejected)
        $donations = Donation::with(['donor', 'charity', 'campaign'])
            ->whereRaw('COALESCE(donated_at, created_at) >= ?', [$startDate])
            ->orderBy(DB::raw('COALESCE(donated_at, created_at)'), 'desc')
            ->get();

        // Fetch fund usage logs (disbursements)
        $usageLogs = FundUsageLog::with(['charity', 'campaign'])
            ->where('spent_at', '>=', $startDate)
            ->orderBy('spent_at', 'desc')
            ->get();

        $donationTx = $donations->map(function ($donation) {
            return [
                'id' => $donation->id,
                'type' => 'donation',
                'amount' => (float) $donation->amount,
                'charity_name' => $donation->charity ? $donation->charity->name : 'Unknown Charity',
                'campaign_name' => $donation->campaign ? $donation->campaign->title : null,
                'donor_name' => $donation->is_anonymous ? 'Anonymous' : ($donation->donor ? $donation->donor->name : $donation->donor_name),
                'date' => ($donation->donated_at ?? $donation->created_at)->toISOString(),
                'status' => $donation->status,
                'is_refunded' => $donation->is_refunded,
                'refunded_at' => $donation->refunded_at,
                'purpose' => $donation->purpose,
                'reference_number' => $donation->reference_number,
            ];
        });

        $disbursementTx = $usageLogs->map(function ($usage) {
            return [
                'id' => $usage->id,
                'type' => 'disbursement',
                'amount' => (float) $usage->amount,
                'charity_name' => $usage->charity ? $usage->charity->name : 'Unknown Charity',
                'campaign_name' => $usage->campaign ? $usage->campaign->title : null,
                'donor_name' => null,
                'date' => $usage->spent_at?->toISOString() ?? $usage->created_at->toISOString(),
                'status' => 'recorded',
                'purpose' => $usage->category,
                'reference_number' => null,
            ];
        });

        $transactions = $donationTx->concat($disbursementTx)
            ->sortByDesc('date')
            ->values();

        return response()->json([
            'transactions' => $transactions,
            'total' => $transactions->count(),
        ]);
        } catch (\Exception $e) {
            \Log::error('getTransactions error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transactions',
                'error' => $e->getMessage(),
                'transactions' => [],
                'total' => 0
            ], 500);
        }
    }

    /**
     * Get chart data for transaction trends
     */
    public function getChartData(Request $request)
    {
        $days = $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days);

        // Group by week or day depending on the period
        $groupBy = $days > 90 ? 'week' : 'day';
        
        if ($groupBy === 'week') {
            $donations = Donation::where('status', 'completed')
                ->where('is_refunded', false)
                ->whereRaw('COALESCE(donated_at, created_at) >= ?', [$startDate])
                ->select(
                    DB::raw('YEARWEEK(COALESCE(donated_at, created_at)) as period'),
                    DB::raw('SUM(amount) as total_amount'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            $disbursements = FundUsageLog::where('spent_at', '>=', $startDate)
                ->select(
                    DB::raw('YEARWEEK(spent_at) as period'),
                    DB::raw('SUM(amount) as total_amount'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            $periods = collect($donations->pluck('period'))
                ->merge($disbursements->pluck('period'))
                ->unique()
                ->sort();

            $chartData = $periods->map(function ($period) use ($donations, $disbursements) {
                $don = $donations->firstWhere('period', $period);
                $dis = $disbursements->firstWhere('period', $period);
                $week = substr($period, 4);
                return [
                    'name' => "Week {$week}",
                    'donations' => (float) ($don->total_amount ?? 0),
                    'disbursements' => (float) ($dis->total_amount ?? 0),
                    'count' => (int) (($don->count ?? 0) + ($dis->count ?? 0)),
                ];
            })->values();
        } else {
            $donations = Donation::where('status', 'completed')
                ->where('is_refunded', false)
                ->whereRaw('COALESCE(donated_at, created_at) >= ?', [$startDate])
                ->select(
                    DB::raw('DATE(COALESCE(donated_at, created_at)) as period'),
                    DB::raw('SUM(amount) as total_amount'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            $disbursements = FundUsageLog::where('spent_at', '>=', $startDate)
                ->select(
                    DB::raw('DATE(spent_at) as period'),
                    DB::raw('SUM(amount) as total_amount'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            $periods = collect($donations->pluck('period'))
                ->merge($disbursements->pluck('period'))
                ->unique()
                ->sort();

            $chartData = $periods->map(function ($period) use ($donations, $disbursements) {
                $don = $donations->firstWhere('period', $period);
                $dis = $disbursements->firstWhere('period', $period);
                return [
                    'name' => Carbon::parse($period)->format('M d'),
                    'donations' => (float) ($don->total_amount ?? 0),
                    'disbursements' => (float) ($dis->total_amount ?? 0),
                    'count' => (int) (($don->count ?? 0) + ($dis->count ?? 0)),
                ];
            })->values();
        }

        return response()->json([
            'chart_data' => $chartData,
            'group_by' => $groupBy,
        ]);
    }

    /**
     * Get pie chart data for fund distribution
     */
    public function getDistributionData(Request $request)
    {
        $days = $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days);

        $totalDonations = Donation::where('status', 'completed')
            ->where('is_refunded', false)
            ->whereRaw('COALESCE(donated_at, created_at) >= ?', [$startDate])
            ->sum('amount');

        $totalDisbursements = FundUsageLog::where('spent_at', '>=', $startDate)
            ->sum('amount');

        return response()->json([
            'distribution' => [
                [
                    'name' => 'Donations',
                    'value' => $totalDonations,
                    'color' => '#10b981',
                ],
                [
                    'name' => 'Disbursements',
                    'value' => $totalDisbursements,
                    'color' => '#ef4444',
                ],
            ],
        ]);
    }

    /**
     * Get breakdown by charity
     */
    public function getCharityBreakdown(Request $request)
    {
        $days = $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days);

        $donationsByCharity = Donation::where('status', 'completed')
            ->whereRaw('COALESCE(donated_at, created_at) >= ?', [$startDate])
            ->select('charity_id', DB::raw('SUM(amount) as total_donations'), DB::raw('COUNT(*) as donation_count'))
            ->groupBy('charity_id')
            ->get();

        $spendingByCharity = FundUsageLog::where('spent_at', '>=', $startDate)
            ->select('charity_id', DB::raw('SUM(amount) as total_spent'), DB::raw('COUNT(*) as spend_count'))
            ->groupBy('charity_id')
            ->get();

        $charities = collect($donationsByCharity->pluck('charity_id'))
            ->merge($spendingByCharity->pluck('charity_id'))
            ->unique();

        $breakdown = $charities->map(function ($charityId) use ($donationsByCharity, $spendingByCharity) {
            $don = $donationsByCharity->firstWhere('charity_id', $charityId);
            $spend = $spendingByCharity->firstWhere('charity_id', $charityId);
            $charity = Charity::select('id','name')->find($charityId);
            return [
                'charity_name' => $charity ? $charity->name : 'Unknown',
                'total_donations' => (float) ($don->total_donations ?? 0),
                'total_spent' => (float) ($spend->total_spent ?? 0),
                'donation_count' => (int) ($don->donation_count ?? 0),
                'spend_count' => (int) ($spend->spend_count ?? 0),
            ];
        })->sortByDesc('total_donations')->values()->take(10);

        return response()->json([
            'breakdown' => $breakdown,
        ]);
    }

    /**
     * Get breakdown by campaign type
     */
    public function getCampaignTypeBreakdown(Request $request)
    {
        $days = $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days);

        // Get donations with campaign types
        $campaignBreakdown = Donation::where('donations.status', 'completed')
            ->whereRaw('COALESCE(donations.donated_at, donations.created_at) >= ?', [$startDate])
            ->whereNotNull('donations.campaign_id')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->select('campaigns.type', DB::raw('SUM(donations.amount) as total_amount'), DB::raw('COUNT(*) as donation_count'))
            ->groupBy('campaigns.type')
            ->orderBy('total_amount', 'desc')
            ->get();

        // Get general donations (no campaign)
        $generalDonations = Donation::where('status', 'completed')
            ->whereRaw('COALESCE(donated_at, created_at) >= ?', [$startDate])
            ->whereNull('campaign_id')
            ->select(DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as donation_count'))
            ->first();

        $breakdown = $campaignBreakdown->map(function ($item) {
            return [
                'type' => ucfirst($item->type),
                'total_amount' => $item->total_amount,
                'donation_count' => $item->donation_count,
            ];
        })->toArray();

        if ($generalDonations && $generalDonations->total_amount > 0) {
            array_unshift($breakdown, [
                'type' => 'General Donations',
                'total_amount' => $generalDonations->total_amount,
                'donation_count' => $generalDonations->donation_count,
            ]);
        }

        return response()->json([
            'breakdown' => $breakdown,
        ]);
    }

    /**
     * Get fund usage by category breakdown
     */
    public function getFundUsageCategoryBreakdown(Request $request)
    {
        $days = $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days);

        $categoryBreakdown = FundUsageLog::where('spent_at', '>=', $startDate)
            ->select('category', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as usage_count'))
            ->groupBy('category')
            ->orderBy('total_amount', 'desc')
            ->get();

        $breakdown = $categoryBreakdown->map(function ($item) {
            return [
                'category' => ucfirst($item->category),
                'total_amount' => (float) $item->total_amount,
                'usage_count' => (int) $item->usage_count,
                'percentage' => 0, // Will be calculated below
            ];
        });

        $totalAmount = $breakdown->sum('total_amount');
        
        if ($totalAmount > 0) {
            $breakdown = $breakdown->map(function ($item) use ($totalAmount) {
                $item['percentage'] = round(($item['total_amount'] / $totalAmount) * 100, 2);
                return $item;
            });
        }

        return response()->json([
            'breakdown' => $breakdown->values(),
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * Get donor engagement metrics
     */
    public function getDonorEngagement(Request $request)
    {
        $days = $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days);

        // Donor retention - donors who donated in both current and previous period
        $previousStartDate = Carbon::now()->subDays($days * 2);
        $previousEndDate = $startDate->copy();

        $currentDonors = Donation::where('status', 'completed')
            ->whereRaw('COALESCE(donated_at, created_at) >= ?', [$startDate])
            ->whereNotNull('donor_id')
            ->pluck('donor_id')
            ->unique();

        $previousDonors = Donation::where('status', 'completed')
            ->whereRaw('COALESCE(donated_at, created_at) >= ?', [$previousStartDate])
            ->whereRaw('COALESCE(donated_at, created_at) < ?', [$previousEndDate])
            ->whereNotNull('donor_id')
            ->pluck('donor_id')
            ->unique();

        $returningDonors = $currentDonors->intersect($previousDonors)->count();
        $retentionRate = $previousDonors->count() > 0 
            ? round(($returningDonors / $previousDonors->count()) * 100, 2) 
            : 0;

        // Top donors
        $topDonors = Donation::where('status', 'completed')
            ->whereRaw('COALESCE(donated_at, created_at) >= ?', [$startDate])
            ->whereNotNull('donor_id')
            ->select('donor_id', DB::raw('SUM(amount) as total_donated'), DB::raw('COUNT(*) as donation_count'))
            ->groupBy('donor_id')
            ->orderBy('total_donated', 'desc')
            ->limit(10)
            ->get();

        $topDonorsList = $topDonors->map(function ($item) {
            $donor = User::select('id', 'name', 'email')->find($item->donor_id);
            return [
                'donor_name' => $donor ? $donor->name : 'Unknown',
                'donor_email' => $donor ? $donor->email : 'N/A',
                'total_donated' => (float) $item->total_donated,
                'donation_count' => (int) $item->donation_count,
            ];
        });

        // Anonymous vs identified donations
        $anonymousDonations = Donation::where('status', 'completed')
            ->whereRaw('COALESCE(donated_at, created_at) >= ?', [$startDate])
            ->where('is_anonymous', true)
            ->count();

        $identifiedDonations = Donation::where('status', 'completed')
            ->whereRaw('COALESCE(donated_at, created_at) >= ?', [$startDate])
            ->where('is_anonymous', false)
            ->count();

        return response()->json([
            'retention_rate' => $retentionRate,
            'returning_donors' => $returningDonors,
            'new_donors' => $currentDonors->count() - $returningDonors,
            'total_unique_donors' => $currentDonors->count(),
            'top_donors' => $topDonorsList,
            'anonymous_donations' => $anonymousDonations,
            'identified_donations' => $identifiedDonations,
        ]);
    }

    /**
     * Get refund tracking data
     */
    public function getRefundTracking(Request $request)
    {
        $days = $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days);

        $refunds = RefundRequest::with(['donation', 'user', 'charity'])
            ->where('created_at', '>=', $startDate)
            ->get();

        $refundsByStatus = $refunds->groupBy('status')->map(function ($group, $status) {
            return [
                'status' => $status,
                'count' => $group->count(),
                'total_amount' => (float) $group->sum('refund_amount'),
            ];
        })->values();

        $recentRefunds = RefundRequest::with(['donation', 'user', 'charity'])
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($refund) {
                return [
                    'id' => $refund->id,
                    'donor_name' => $refund->user ? $refund->user->name : 'Unknown',
                    'charity_name' => $refund->charity ? $refund->charity->name : 'Unknown',
                    'amount' => (float) $refund->refund_amount,
                    'status' => $refund->status,
                    'reason' => $refund->reason,
                    'requested_at' => $refund->created_at->toISOString(),
                ];
            });

        return response()->json([
            'refunds_by_status' => $refundsByStatus,
            'recent_refunds' => $recentRefunds,
            'total_refunds' => $refunds->count(),
            'total_refund_amount' => (float) $refunds->sum('refund_amount'),
            'pending_count' => $refunds->where('status', 'pending')->count(),
            'approved_count' => $refunds->where('status', 'approved')->count(),
            'denied_count' => $refunds->where('status', 'denied')->count(),
        ]);
    }

    /**
     * Get recurring donation statistics
     */
    public function getRecurringStats(Request $request)
    {
        $recurringDonations = RecurringDonation::with(['user', 'charity', 'campaign'])->get();

        $byInterval = $recurringDonations->groupBy('interval')->map(function ($group, $interval) {
            return [
                'interval' => $interval,
                'count' => $group->count(),
                'total_amount' => (float) $group->sum('total_amount'),
                'active_count' => $group->where('status', 'active')->count(),
            ];
        })->values();

        $byStatus = $recurringDonations->groupBy('status')->map(function ($group, $status) {
            return [
                'status' => $status,
                'count' => $group->count(),
                'total_amount' => (float) $group->sum('total_amount'),
            ];
        })->values();

        return response()->json([
            'by_interval' => $byInterval,
            'by_status' => $byStatus,
            'total_recurring' => $recurringDonations->count(),
            'active_recurring' => $recurringDonations->where('status', 'active')->count(),
            'total_recurring_revenue' => (float) $recurringDonations->sum('total_amount'),
        ]);
    }

    /**
     * Get donation purpose breakdown
     */
    public function getDonationPurposeBreakdown(Request $request)
    {
        $days = $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days);

        $purposeBreakdown = Donation::where('status', 'completed')
            ->whereRaw('COALESCE(donated_at, created_at) >= ?', [$startDate])
            ->select('purpose', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as donation_count'))
            ->groupBy('purpose')
            ->orderBy('total_amount', 'desc')
            ->get();

        $breakdown = $purposeBreakdown->map(function ($item) {
            return [
                'purpose' => ucfirst($item->purpose),
                'total_amount' => (float) $item->total_amount,
                'donation_count' => (int) $item->donation_count,
            ];
        });

        return response()->json([
            'breakdown' => $breakdown,
        ]);
    }

    /**
     * Get overfunded campaigns analytics
     * Campaigns that reached 100% of target but continue receiving donations
     */
    public function getOverfundedCampaigns(Request $request)
    {
        $days = $request->input('days', null); // null means all time
        $limit = $request->input('limit', 20);

        // Get campaigns with their current amounts
        $campaignsQuery = Campaign::with(['charity', 'donations' => function ($query) {
            $query->where('status', 'completed');
        }])
        ->whereNotNull('target_amount')
        ->where('target_amount', '>', 0)
        ->where('status', '!=', 'draft');

        $campaigns = $campaignsQuery->get();

        // Filter campaigns that are overfunded (current_amount >= target_amount)
        $overfundedCampaigns = $campaigns->filter(function ($campaign) {
            return $campaign->current_amount >= $campaign->target_amount;
        });

        // Calculate detailed metrics for each overfunded campaign
        $detailedCampaigns = $overfundedCampaigns->map(function ($campaign) use ($days) {
            $targetAmount = (float) $campaign->target_amount;
            $currentAmount = $campaign->current_amount;
            $excessAmount = $currentAmount - $targetAmount;
            $fundingPercentage = $targetAmount > 0 ? ($currentAmount / $targetAmount) * 100 : 0;

            // Get donations after reaching 100%
            $allDonations = $campaign->donations()
                ->where('status', 'completed')
                ->orderBy('donated_at')
                ->get();

            $runningTotal = 0;
            $dateReached100 = null;
            $donationsAfter100 = collect();

            foreach ($allDonations as $donation) {
                $runningTotal += $donation->amount;
                
                if ($dateReached100 === null && $runningTotal >= $targetAmount) {
                    $dateReached100 = $donation->donated_at ?? $donation->created_at;
                }

                if ($dateReached100 !== null && ($donation->donated_at ?? $donation->created_at)->gt($dateReached100)) {
                    $donationsAfter100->push($donation);
                }
            }

            // Filter by date range if specified
            if ($days !== null) {
                $startDate = Carbon::now()->subDays($days);
                $donationsAfter100 = $donationsAfter100->filter(function ($donation) use ($startDate) {
                    return ($donation->donated_at ?? $donation->created_at) >= $startDate;
                });
            }

            $donationsAfter100Count = $donationsAfter100->count();
            $amountAfter100 = $donationsAfter100->sum('amount');

            return [
                'campaign_id' => $campaign->id,
                'campaign_title' => $campaign->title,
                'charity_name' => $campaign->charity ? $campaign->charity->name : 'Unknown',
                'target_amount' => $targetAmount,
                'current_amount' => $currentAmount,
                'excess_amount' => $excessAmount,
                'funding_percentage' => round($fundingPercentage, 2),
                'overfunded_by_percent' => round($fundingPercentage - 100, 2),
                'date_reached_100_percent' => $dateReached100 ? $dateReached100->toISOString() : null,
                'days_since_100_percent' => $dateReached100 ? Carbon::parse($dateReached100)->diffInDays(Carbon::now()) : null,
                'donations_after_100_count' => $donationsAfter100Count,
                'amount_received_after_100' => (float) $amountAfter100,
                'total_donors' => $campaign->donors_count,
                'status' => $campaign->status,
                'deadline_at' => $campaign->deadline_at ? $campaign->deadline_at->toISOString() : null,
            ];
        })
        ->sortByDesc('overfunded_by_percent')
        ->values()
        ->take($limit);

        // Summary statistics
        $summary = [
            'total_overfunded_campaigns' => $overfundedCampaigns->count(),
            'total_excess_funds' => (float) $detailedCampaigns->sum('excess_amount'),
            'average_overfunding_percentage' => $detailedCampaigns->count() > 0 
                ? round($detailedCampaigns->avg('overfunded_by_percent'), 2) 
                : 0,
            'highest_overfunding_percentage' => $detailedCampaigns->count() > 0 
                ? round($detailedCampaigns->max('overfunded_by_percent'), 2) 
                : 0,
            'total_donations_after_100' => (int) $detailedCampaigns->sum('donations_after_100_count'),
            'total_amount_after_100' => (float) $detailedCampaigns->sum('amount_received_after_100'),
        ];

        return response()->json([
            'summary' => $summary,
            'campaigns' => $detailedCampaigns,
            'period_days' => $days,
        ]);
    }

    /**
     * Get campaign donation timeline
     * Shows all donations for a specific campaign in chronological order
     */
    public function getCampaignDonationTimeline(Request $request, $campaignId)
    {
        $campaign = Campaign::with(['charity'])->find($campaignId);

        if (!$campaign) {
            return response()->json(['error' => 'Campaign not found'], 404);
        }

        $donations = Donation::with(['donor', 'charity'])
            ->where('campaign_id', $campaignId)
            ->orderBy('donated_at', 'asc')
            ->get();

        $runningTotal = 0;
        $targetAmount = (float) $campaign->target_amount;
        $reached100Date = null;

        $timeline = $donations->map(function ($donation) use (&$runningTotal, $targetAmount, &$reached100Date) {
            $runningTotal += $donation->amount;
            $progressPercentage = $targetAmount > 0 ? ($runningTotal / $targetAmount) * 100 : 0;
            
            // Mark when 100% was reached
            $justReached100 = false;
            if ($reached100Date === null && $progressPercentage >= 100) {
                $reached100Date = $donation->donated_at ?? $donation->created_at;
                $justReached100 = true;
            }

            return [
                'id' => $donation->id,
                'donor_name' => $donation->is_anonymous ? 'Anonymous' : ($donation->donor ? $donation->donor->name : $donation->donor_name),
                'amount' => (float) $donation->amount,
                'running_total' => $runningTotal,
                'progress_percentage' => round($progressPercentage, 2),
                'status' => $donation->status,
                'donated_at' => ($donation->donated_at ?? $donation->created_at)->toISOString(),
                'is_after_100_percent' => $reached100Date !== null && ($donation->donated_at ?? $donation->created_at)->gt($reached100Date),
                'milestone_reached' => $justReached100 ? '100% Funded!' : null,
            ];
        });

        return response()->json([
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'charity_name' => $campaign->charity ? $campaign->charity->name : 'Unknown',
                'target_amount' => $targetAmount,
                'current_amount' => $campaign->current_amount,
                'total_donors' => $campaign->donors_count,
                'status' => $campaign->status,
            ],
            'timeline' => $timeline,
            'total_donations' => $donations->count(),
            'date_reached_100_percent' => $reached100Date ? $reached100Date->toISOString() : null,
        ]);
    }

    /**
     * Export fund tracking data as CSV
     */
    public function exportData(Request $request)
    {
        $days = $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days);

        // Export ALL donations (including pending) and fund usage logs for complete admin records
        $donations = Donation::with(['donor', 'charity', 'campaign'])
            ->whereRaw('COALESCE(donated_at, created_at) >= ?', [$startDate])
            ->orderBy(DB::raw('COALESCE(donated_at, created_at)'), 'desc')
            ->get();

        $usageLogs = FundUsageLog::with(['charity', 'campaign'])
            ->where('spent_at', '>=', $startDate)
            ->orderBy('spent_at', 'desc')
            ->get();

        $csvData = [];
        $csvData[] = ['ID', 'Date', 'Type', 'Donor', 'Charity', 'Campaign', 'Amount', 'Status', 'Reference'];

        foreach ($donations as $donation) {
            $csvData[] = [
                $donation->id,
                ($donation->donated_at ?? $donation->created_at)->format('Y-m-d H:i:s'),
                'Donation',
                $donation->is_anonymous ? 'Anonymous' : ($donation->donor ? $donation->donor->name : $donation->donor_name),
                $donation->charity ? $donation->charity->name : 'N/A',
                $donation->campaign ? $donation->campaign->title : 'General',
                $donation->amount,
                $donation->status,
                $donation->external_ref ?? 'N/A',
            ];
        }

        foreach ($usageLogs as $usage) {
            $csvData[] = [
                $usage->id,
                ($usage->spent_at ?? $usage->created_at)->format('Y-m-d H:i:s'),
                'Disbursement',
                'N/A',
                $usage->charity ? $usage->charity->name : 'N/A',
                $usage->campaign ? $usage->campaign->title : 'General',
                $usage->amount,
                'recorded',
                'N/A',
            ];
        }

        $filename = 'charityhub_fund_tracking_' . date('Y-m-d_His') . '.csv';
        
        $handle = fopen('php://temp', 'r+');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }
    
    /**
     * Export platform report as PDF
     */
    public function exportPlatformPDF(Request $request)
    {
        $days = $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days);
        $endDate = Carbon::now();

        // Gather platform statistics
        $stats = [
            'total_users' => User::whereNotNull('email_verified_at')->count(),
            'donors' => User::where('role', 'donor')->whereNotNull('email_verified_at')->count(),
            'charity_admins' => User::where('role', 'charity_admin')->whereNotNull('email_verified_at')->count(),
            'active_users' => User::where('status', 'active')->whereNotNull('email_verified_at')->count(),
            
            'total_charities' => Charity::count(),
            'verified_charities' => Charity::where('verification_status', 'approved')->count(),
            'pending_charities' => Charity::where('verification_status', 'pending')->count(),
            
            'active_campaigns' => Campaign::where('status', 'published')->count(),
            'total_donations' => Donation::count(),
            'completed_donations' => Donation::where('status', 'completed')->count(),
            'pending_donations' => Donation::where('status', 'pending')->count(),
            'total_raised' => Donation::where('status', 'completed')->where('is_refunded', false)->sum('amount'),
            'average_donation' => Donation::where('status', 'completed')->where('is_refunded', false)->avg('amount') ?? 0,
        ];

        // Top charities
        $topCharities = DB::table('charities')
            ->join('donations', 'charities.id', '=', 'donations.charity_id')
            ->where('donations.status', 'completed')
            ->where('donations.is_refunded', false)
            ->select(
                'charities.name',
                DB::raw('SUM(donations.amount) as total'),
                DB::raw('COUNT(DISTINCT donations.id) as donations'),
                DB::raw('COUNT(DISTINCT donations.campaign_id) as campaigns')
            )
            ->groupBy('charities.id', 'charities.name')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        // Monthly trend
        $monthlyTrend = DB::table('donations')
            ->where('status', 'completed')
            ->where('is_refunded', false)
            ->where('donated_at', '>=', $startDate)
            ->select(
                DB::raw('DATE_FORMAT(donated_at, "%Y-%m") as month'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(id) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();

        // Campaign types
        $campaignTypes = DB::table('campaigns')
            ->leftJoin('donations', function($join) {
                $join->on('campaigns.id', '=', 'donations.campaign_id')
                    ->where('donations.status', '=', 'completed')
                    ->where('donations.is_refunded', '=', false);
            })
            ->whereNotNull('campaigns.type')
            ->select(
                'campaigns.type',
                DB::raw('COUNT(DISTINCT campaigns.id) as count'),
                DB::raw('COALESCE(SUM(donations.amount), 0) as total')
            )
            ->groupBy('campaigns.type')
            ->get()
            ->toArray();

        $data = [
            'period' => [
                'start' => $startDate->format('F d, Y'),
                'end' => $endDate->format('F d, Y'),
            ],
            'stats' => $stats,
            'top_charities' => $topCharities,
            'monthly_trend' => $monthlyTrend,
            'campaign_types' => $campaignTypes,
        ];

        $filename = \App\Helpers\ReportGenerator::generateFilename('platform_report');
        
        return \App\Helpers\ReportGenerator::generatePDF('reports.platform-report', $data, $filename);
    }
}

