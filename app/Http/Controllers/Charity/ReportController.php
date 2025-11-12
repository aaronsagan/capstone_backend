<?php

namespace App\Http\Controllers\Charity;

use App\Http\Controllers\Controller;
use App\Models\{Charity, Donation, Campaign};
use App\Helpers\ReportGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Export charity report as PDF
     */
    public function exportPDF(Request $request)
    {
        $user = $request->user();
        
        // Get charity
        $charity = Charity::where('owner_id', $user->id)->firstOrFail();
        
        // Get date range
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
            ->whereBetween('donated_at', [$startDate, $endDate])
            ->with(['donor:id,name', 'campaign:id,title'])
            ->orderBy('donated_at', 'desc')
            ->get();
        
        $completedDonations = $donations->where('status', 'completed');
        
        // Calculate summary
        $summary = [
            'total_raised' => $completedDonations->sum('amount'),
            'donation_count' => $completedDonations->count(),
            'unique_donors' => $completedDonations->whereNotNull('donor_id')->pluck('donor_id')->unique()->count(),
            'active_campaigns' => Campaign::where('charity_id', $charity->id)
                ->where('status', 'published')
                ->count(),
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
        
        // Get breakdown by campaign
        $byCampaign = DB::table('donations')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->where('donations.charity_id', $charity->id)
            ->where('donations.status', 'completed')
            ->whereBetween('donations.donated_at', [$startDate, $endDate])
            ->select('campaigns.title', 'campaigns.status',
                     DB::raw('SUM(donations.amount) as total'),
                     DB::raw('COUNT(DISTINCT donations.donor_id) as donors'))
            ->groupBy('campaigns.id', 'campaigns.title', 'campaigns.status')
            ->orderByDesc('total')
            ->get()
            ->toArray();
        
        // Get monthly trend
        $monthlyTrend = DB::table('donations')
            ->where('charity_id', $charity->id)
            ->where('status', 'completed')
            ->whereBetween('donated_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE_FORMAT(donated_at, "%Y-%m") as month'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(id) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
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
            'by_campaign' => $byCampaign,
            'monthly_trend' => $monthlyTrend,
        ];
        
        // Generate filename: charity_audit_report_{charityname}_{date}.pdf
        $charityName = str_replace(' ', '_', strtolower($charity->name));
        $filename = 'charity_audit_report_' . $charityName . '_' . Carbon::now()->format('Y-m-d') . '.pdf';
        
        return ReportGenerator::generatePDF('reports.charity-report', $data, $filename);
    }
    
    /**
     * Export charity donations as CSV
     */
    public function exportCSV(Request $request)
    {
        $user = $request->user();
        
        // Get charity
        $charity = Charity::where('owner_id', $user->id)->firstOrFail();
        
        // Get date range
        $startDate = $request->input('start_date', Carbon::now()->subYear());
        $endDate = $request->input('end_date', Carbon::now());
        
        if (is_string($startDate)) {
            $startDate = Carbon::parse($startDate);
        }
        if (is_string($endDate)) {
            $endDate = Carbon::parse($endDate);
        }
        
        // Get donations
        $donations = Donation::where('charity_id', $charity->id)
            ->whereBetween('donated_at', [$startDate, $endDate])
            ->with(['donor:id,name', 'campaign:id,title'])
            ->orderBy('donated_at', 'desc')
            ->get();
        
        // Prepare headers
        $headers = [
            'Date',
            'Donor',
            'Email',
            'Campaign',
            'Amount',
            'Status',
            'Reference',
            'Purpose',
            'Anonymous',
        ];
        
        // Prepare data rows
        $rows = $donations->map(function ($donation) {
            return [
                ($donation->donated_at ?? $donation->created_at)->format('Y-m-d H:i:s'),
                $donation->is_anonymous ? 'Anonymous' : ($donation->donor->name ?? 'Unknown'),
                $donation->is_anonymous ? '' : ($donation->donor->email ?? ''),
                $donation->campaign->title ?? 'General Donation',
                number_format($donation->amount, 2),
                ucfirst($donation->status),
                $donation->external_ref ?? $donation->receipt_no ?? 'N/A',
                ucfirst($donation->purpose),
                $donation->is_anonymous ? 'Yes' : 'No',
            ];
        })->toArray();
        
        // Generate CSV
        $csv = ReportGenerator::generateCSV($headers, $rows);
        
        // Generate filename: charity_donations_{date}.csv
        $filename = 'charity_donations_' . Carbon::now()->format('Y-m-d') . '.csv';
        
        return ReportGenerator::downloadCSV($csv, $filename);
    }
}
