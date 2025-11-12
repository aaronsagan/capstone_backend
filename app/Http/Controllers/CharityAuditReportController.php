<?php

namespace App\Http\Controllers;

use App\Models\{Charity, Donation, Campaign};
use App\Helpers\ReportGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CharityAuditReportController extends Controller
{
    /**
     * Generate charity audit report (PDF)
     * Shows all received donations, campaign stats, and top donors
     */
    public function generateAuditReport(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            
            // Get charity - with better error handling
            $charity = Charity::where('owner_id', $user->id)->first();
            
            if (!$charity) {
                return response()->json([
                    'error' => 'No charity found for your account',
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'message' => 'Please contact support if you believe this is an error.'
                ], 404);
            }
        
        // Get time range (default to last year)
        $startDate = $request->input('start_date', Carbon::now()->subYear()->startOfDay());
        $endDate = $request->input('end_date', Carbon::now()->endOfDay());
        
        if (is_string($startDate)) {
            $startDate = Carbon::parse($startDate);
        }
        if (is_string($endDate)) {
            $endDate = Carbon::parse($endDate);
        }
        
        // Get all donations for this charity
        $donations = Donation::where('charity_id', $charity->id)
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('donated_at', [$startDate, $endDate])
                      ->orWhereBetween('created_at', [$startDate, $endDate]);
            })
            ->with(['donor:id,name,email', 'campaign:id,title'])
            ->orderBy('donated_at', 'desc')
            ->get();
        
        $completedDonations = $donations->where('status', 'completed');
        
        // Calculate summary statistics
        $summary = [
            'total_raised' => $completedDonations->sum('amount'),
            'donation_count' => $completedDonations->count(),
            'unique_donors' => $completedDonations->whereNotNull('donor_id')->pluck('donor_id')->unique()->count(),
            'active_campaigns' => Campaign::where('charity_id', $charity->id)
                ->where('status', 'published')
                ->count(),
            'total_campaigns' => Campaign::where('charity_id', $charity->id)->count(),
        ];
        
        // Format donations for PDF
        $formattedDonations = $donations->map(function ($donation) {
            return [
                'date' => $donation->donated_at ?? $donation->created_at,
                'donor' => $donation->donor->name ?? 'Unknown Donor',
                'is_anonymous' => $donation->is_anonymous ?? false,
                'campaign' => $donation->campaign->title ?? null,
                'amount' => $donation->amount,
                'status' => $donation->status,
                'reference' => $donation->external_ref ?? $donation->receipt_no,
            ];
        })->toArray();
        
        // Get top donors (respecting anonymity)
        $topDonors = DB::table('donations')
            ->join('users', 'donations.donor_id', '=', 'users.id')
            ->where('donations.charity_id', $charity->id)
            ->where('donations.status', 'completed')
            ->where('donations.is_anonymous', false)
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('donations.donated_at', [$startDate, $endDate])
                      ->orWhereBetween('donations.created_at', [$startDate, $endDate]);
            })
            ->select('users.name', 
                     DB::raw('SUM(donations.amount) as total'),
                     DB::raw('COUNT(donations.id) as count'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->name,
                    'total' => $item->total,
                    'count' => $item->count
                ];
            })
            ->toArray();
        
        // Get breakdown by campaign
        $byCampaign = DB::table('donations')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->where('donations.charity_id', $charity->id)
            ->where('donations.status', 'completed')
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('donations.donated_at', [$startDate, $endDate])
                      ->orWhereBetween('donations.created_at', [$startDate, $endDate]);
            })
            ->select('campaigns.title', 'campaigns.status',
                     DB::raw('SUM(donations.amount) as total'),
                     DB::raw('COUNT(DISTINCT donations.donor_id) as donors'))
            ->groupBy('campaigns.id', 'campaigns.title', 'campaigns.status')
            ->orderByDesc('total')
            ->get()
            ->map(function($item) {
                return [
                    'title' => $item->title,
                    'status' => $item->status,
                    'total' => $item->total,
                    'donors' => $item->donors
                ];
            })
            ->toArray();
        
        // Get monthly trend
        $monthlyTrend = DB::table('donations')
            ->where('charity_id', $charity->id)
            ->where('status', 'completed')
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('donated_at', [$startDate, $endDate])
                      ->orWhereBetween('created_at', [$startDate, $endDate]);
            })
            ->select(
                DB::raw('DATE_FORMAT(COALESCE(donated_at, created_at), "%Y-%m") as month'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(id) as count')
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
        
        // Prepare data for PDF
        $data = [
            'charity' => [
                'name' => $charity->name,
                'reg_no' => $charity->reg_no,
            ],
            'period' => [
                'start' => $startDate->format('F d, Y'),
                'end' => $endDate->format('F d, Y'),
            ],
            'summary' => $summary,
            'donations' => $formattedDonations,
            'top_donors' => $topDonors,
            'by_campaign' => $byCampaign,
            'monthly_trend' => $monthlyTrend,
        ];
        
        // Generate filename: charity_audit_report_{charityname}_{date}.pdf
        $charityName = str_replace(' ', '_', strtolower($charity->name));
        $filename = 'charity_audit_report_' . $charityName . '_' . Carbon::now()->format('Y-m-d') . '.pdf';
        
        return ReportGenerator::generatePDF('reports.charity-report', $data, $filename);
        
        } catch (\Exception $e) {
            \Log::error('Charity Audit Report Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'error' => 'Failed to generate report',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Export charity donations as CSV
     * CSV list of all donations received for internal tracking
     */
    public function exportCSV(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            
            // Get charity
            $charity = Charity::where('owner_id', $user->id)->first();
            
            if (!$charity) {
                return response()->json([
                    'error' => 'No charity found for your account'
                ], 404);
            }
        
        // Get date range
        $startDate = $request->input('start_date', Carbon::now()->subYear());
        $endDate = $request->input('end_date', Carbon::now());
        
        if (is_string($startDate)) {
            $startDate = Carbon::parse($startDate);
        }
        if (is_string($endDate)) {
            $endDate = Carbon::parse($endDate);
        }
        
        // Get all donations
        $donations = Donation::where('charity_id', $charity->id)
            ->whereBetween('donated_at', [$startDate, $endDate])
            ->with(['donor:id,name,email', 'campaign:id,title'])
            ->orderBy('donated_at', 'desc')
            ->get();
        
        // CSV Headers as specified: Donor Name, Donation Amount, Campaign, Date, Status
        $headers = [
            'Donor Name',
            'Donation Amount',
            'Campaign',
            'Date',
            'Status',
        ];
        
        // Prepare data rows
        $rows = $donations->map(function ($donation) {
            return [
                $donation->is_anonymous ? 'Anonymous' : ($donation->donor->name ?? 'Unknown'),
                number_format($donation->amount, 2),
                $donation->campaign->title ?? 'General Donation',
                ($donation->donated_at ?? $donation->created_at)->format('Y-m-d H:i:s'),
                ucfirst($donation->status),
            ];
        })->toArray();
        
        // Generate CSV
        $csv = ReportGenerator::generateCSV($headers, $rows);
        
        // Generate filename: charity_donations_{date}.csv
        $filename = 'charity_donations_' . Carbon::now()->format('Y-m-d') . '.csv';
        
        return ReportGenerator::downloadCSV($csv, $filename);
        
        } catch (\Exception $e) {
            \Log::error('Charity CSV Export Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to export CSV',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
