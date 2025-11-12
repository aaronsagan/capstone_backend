<?php

namespace App\Http\Controllers\Donor;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Helpers\ReportGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Export donor statement as PDF
     */
    public function exportPDF(Request $request)
    {
        $user = $request->user();
        
        // Get date range
        $startDate = $request->input('start_date', Carbon::now()->subYear()->startOfDay());
        $endDate = $request->input('end_date', Carbon::now()->endOfDay());
        
        if (is_string($startDate)) {
            $startDate = Carbon::parse($startDate);
        }
        if (is_string($endDate)) {
            $endDate = Carbon::parse($endDate);
        }
        
        // Get all donations for this donor
        $donations = Donation::where('donor_id', $user->id)
            ->whereBetween('donated_at', [$startDate, $endDate])
            ->with(['charity:id,name', 'campaign:id,title'])
            ->orderBy('donated_at', 'desc')
            ->get();
        
        // Separate completed donations into active and refunded
        $completedDonations = $donations->where('status', 'completed');
        $activeDonations = $completedDonations->where('is_refunded', false);
        $refundedDonations = $completedDonations->where('is_refunded', true);
        
        // Calculate summary (exclude refunded from totals)
        $summary = [
            'total_donated' => $activeDonations->sum('amount'),
            'total_count' => $activeDonations->count(),
            'charities_count' => $activeDonations->pluck('charity_id')->unique()->count(),
            'average_donation' => $activeDonations->count() > 0 
                ? $activeDonations->sum('amount') / $activeDonations->count() 
                : 0,
            'refunded_amount' => $refundedDonations->sum('amount'),
            'refunded_count' => $refundedDonations->count(),
        ];
        
        // Format donations for PDF
        $formattedDonations = $donations->map(function ($donation) {
            return [
                'date' => $donation->donated_at ?? $donation->created_at,
                'charity' => $donation->charity->name ?? 'Unknown',
                'campaign' => $donation->campaign->title ?? null,
                'amount' => $donation->amount,
                'status' => $donation->status,
                'receipt_no' => $donation->receipt_no,
            ];
        })->toArray();
        
        // Get breakdown by charity (exclude refunded)
        $byCharity = DB::table('donations')
            ->join('charities', 'donations.charity_id', '=', 'charities.id')
            ->where('donations.donor_id', $user->id)
            ->where('donations.status', 'completed')
            ->where('donations.is_refunded', false)
            ->whereBetween('donations.donated_at', [$startDate, $endDate])
            ->select('charities.name', 
                     DB::raw('SUM(donations.amount) as total'),
                     DB::raw('COUNT(donations.id) as count'))
            ->groupBy('charities.name')
            ->orderByDesc('total')
            ->get()
            ->toArray();
        
        // Prepare data for PDF
        $data = [
            'donor' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'period' => [
                'start' => $startDate->format('F d, Y'),
                'end' => $endDate->format('F d, Y'),
            ],
            'summary' => $summary,
            'donations' => $formattedDonations,
            'by_charity' => $byCharity,
        ];
        
        // Generate filename: donor_audit_report_{date}.pdf
        $filename = 'donor_audit_report_' . Carbon::now()->format('Y-m-d') . '.pdf';
        
        return ReportGenerator::generatePDF('reports.donor-statement', $data, $filename);
    }
    
    /**
     * Export donor donations as CSV
     */
    public function exportCSV(Request $request)
    {
        $user = $request->user();
        
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
        $donations = Donation::where('donor_id', $user->id)
            ->whereBetween('donated_at', [$startDate, $endDate])
            ->with(['charity:id,name', 'campaign:id,title'])
            ->orderBy('donated_at', 'desc')
            ->get();
        
        // Prepare headers
        $headers = [
            'Date',
            'Charity',
            'Campaign',
            'Amount',
            'Status',
            'Receipt Number',
            'Reference Number',
            'Purpose',
        ];
        
        // Prepare data rows
        $rows = $donations->map(function ($donation) {
            return [
                ($donation->donated_at ?? $donation->created_at)->format('Y-m-d H:i:s'),
                $donation->charity->name ?? 'Unknown',
                $donation->campaign->title ?? 'General Donation',
                number_format($donation->amount, 2),
                ucfirst($donation->status),
                $donation->receipt_no ?? 'N/A',
                $donation->external_ref ?? 'N/A',
                ucfirst($donation->purpose),
            ];
        })->toArray();
        
        // Generate CSV
        $csv = ReportGenerator::generateCSV($headers, $rows);
        
        // Generate filename: donations_{donorname}_{date}.csv
        $donorName = str_replace(' ', '_', strtolower($user->name));
        $filename = 'donations_' . $donorName . '_' . Carbon::now()->format('Y-m-d') . '.csv';
        
        return ReportGenerator::downloadCSV($csv, $filename);
    }
}
