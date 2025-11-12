<?php

namespace App\Http\Controllers;

use App\Models\{User, Charity, Campaign, Donation};
use App\Helpers\ReportGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PlatformReportController extends Controller
{
    /**
     * Generate platform-wide report (PDF)
     * System-level report summarizing total users, donations, campaigns, and top charities
     */
    public function generatePlatformReport(Request $request)
    {
        // Get time range (default to last 90 days)
        $days = $request->input('days', 90);
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        if ($request->has('start_date')) {
            $startDate = Carbon::parse($request->input('start_date'));
        }
        if ($request->has('end_date')) {
            $endDate = Carbon::parse($request->input('end_date'));
        }
        
        // Gather comprehensive platform statistics
        $stats = [
            // User statistics
            'total_users' => User::whereNotNull('email_verified_at')->count(),
            'donors' => User::where('role', 'donor')->whereNotNull('email_verified_at')->count(),
            'charity_admins' => User::where('role', 'charity_admin')->whereNotNull('email_verified_at')->count(),
            'active_users' => User::where('status', 'active')
                ->whereNotNull('email_verified_at')
                ->where('last_login_at', '>=', Carbon::now()->subDays(30))
                ->count(),
            
            // Charity statistics
            'total_charities' => Charity::count(),
            'verified_charities' => Charity::where('verification_status', 'approved')->count(),
            'pending_charities' => Charity::where('verification_status', 'pending')->count(),
            
            // Campaign statistics
            'total_campaigns' => Campaign::count(),
            'active_campaigns' => Campaign::where('status', 'published')->count(),
            'closed_campaigns' => Campaign::where('status', 'closed')->count(),
            
            // Donation statistics
            'total_donations' => Donation::count(),
            'completed_donations' => Donation::where('status', 'completed')->count(),
            'pending_donations' => Donation::where('status', 'pending')->count(),
            'total_raised' => Donation::where('status', 'completed')->where('is_refunded', false)->sum('amount'),
            'average_donation' => Donation::where('status', 'completed')->where('is_refunded', false)->avg('amount') ?? 0,
            
            // Period-specific stats
            'period_donations' => Donation::where('status', 'completed')
                ->where('is_refunded', false)
                ->whereBetween('donated_at', [$startDate, $endDate])
                ->count(),
            'period_raised' => Donation::where('status', 'completed')
                ->where('is_refunded', false)
                ->whereBetween('donated_at', [$startDate, $endDate])
                ->sum('amount'),
        ];
        
        // Top performing charities
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
        
        // Monthly donation trend
        $monthlyTrend = DB::table('donations')
            ->where('status', 'completed')
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
        
        // Campaign type distribution
        $campaignTypes = DB::table('campaigns')
            ->leftJoin('donations', function($join) {
                $join->on('campaigns.id', '=', 'donations.campaign_id')
                    ->where('donations.status', '=', 'completed');
            })
            ->whereNotNull('campaigns.type')
            ->select(
                'campaigns.type',
                DB::raw('COUNT(DISTINCT campaigns.id) as count'),
                DB::raw('COALESCE(SUM(donations.amount), 0) as total')
            )
            ->groupBy('campaigns.type')
            ->orderBy('total', 'desc')
            ->get()
            ->toArray();
        
        // Prepare data for PDF
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
        
        // Generate filename: platform_report_{month_year}.pdf
        $monthYear = Carbon::now()->format('F_Y');
        $filename = 'platform_report_' . $monthYear . '.pdf';
        
        return ReportGenerator::generatePDF('reports.platform-report', $data, $filename);
    }
}
