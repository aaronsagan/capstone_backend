<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Helpers\ReportGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DonorAuditReportController extends Controller
{
    /**
     * Generate donor audit report (PDF)
     * Generates a personal donation summary for audit or record purposes
     */
    public function generateAuditReport(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
        
        // Get date range (default to last year)
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
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('donated_at', [$startDate, $endDate])
                      ->orWhereBetween('created_at', [$startDate, $endDate]);
            })
            ->with(['charity:id,name', 'campaign:id,title'])
            ->orderBy('donated_at', 'desc')
            ->get();
        
        $completedDonations = $donations->where('status', 'completed');
        $activeDonations = $completedDonations->where('is_refunded', false);
        $refundedDonations = $completedDonations->where('is_refunded', true);
        
        // Calculate comprehensive summary (exclude refunded)
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
        
        // Format all donations for PDF table
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
        
        // Get breakdown by charity (supported charities, exclude refunded)
        $byCharity = DB::table('donations')
            ->join('charities', 'donations.charity_id', '=', 'charities.id')
            ->where('donations.donor_id', $user->id)
            ->where('donations.status', 'completed')
            ->where('donations.is_refunded', false)
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('donations.donated_at', [$startDate, $endDate])
                      ->orWhereBetween('donations.created_at', [$startDate, $endDate]);
            })
            ->select('charities.name', 
                     DB::raw('SUM(donations.amount) as total'),
                     DB::raw('COUNT(donations.id) as count'))
            ->groupBy('charities.name')
            ->orderByDesc('total')
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->name,
                    'total' => $item->total,
                    'count' => $item->count
                ];
            })
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
        
        } catch (\Exception $e) {
            \Log::error('Donor Audit Report Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'error' => 'Failed to generate donor report',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
    
    /**
     * Export donor donations as CSV
     */
    public function exportCSV(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
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
        $donations = Donation::where('donor_id', $user->id)
            ->whereBetween('donated_at', [$startDate, $endDate])
            ->with(['charity:id,name', 'campaign:id,title'])
            ->orderBy('donated_at', 'desc')
            ->get();
        
        // CSV Headers as specified: Date, Campaign, Charity, Amount, Status
        $headers = [
            'Date',
            'Campaign',
            'Charity',
            'Amount',
            'Status',
        ];
        
        // Prepare data rows
        $rows = $donations->map(function ($donation) {
            return [
                ($donation->donated_at ?? $donation->created_at)->format('Y-m-d H:i:s'),
                $donation->campaign->title ?? 'General Donation',
                $donation->charity->name ?? 'Unknown',
                number_format($donation->amount, 2),
                ucfirst($donation->status),
            ];
        })->toArray();
        
        // Generate CSV
        $csv = ReportGenerator::generateCSV($headers, $rows);
        
        // Generate filename: donations_{donorname}_{date}.csv
        $donorName = str_replace(' ', '_', strtolower($user->name));
        $filename = 'donations_' . $donorName . '_' . Carbon::now()->format('Y-m-d') . '.csv';
        
        return ReportGenerator::downloadCSV($csv, $filename);
        
        } catch (\Exception $e) {
            \Log::error('Donor CSV Export Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to export CSV',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
