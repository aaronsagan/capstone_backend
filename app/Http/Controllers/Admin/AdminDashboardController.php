<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Charity;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * Get charity and donor registrations trend (monthly for last 6 months)
     */
    public function getRegistrationsTrend(Request $request)
    {
        $months = $request->input('months', 6);
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();
        
        // Get monthly charity registrations (charity_admin role)
        $charityRegistrations = User::where('role', 'charity_admin')
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy('month');
        
        // Get monthly donor registrations
        $donorRegistrations = User::where('role', 'donor')
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy('month');
        
        // Generate data for all months
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $monthLabel = $date->format('M');
            
            $data[] = [
                'month' => $monthLabel,
                'charities' => $charityRegistrations->get($monthKey)->count ?? 0,
                'donors' => $donorRegistrations->get($monthKey)->count ?? 0,
                'total' => ($charityRegistrations->get($monthKey)->count ?? 0) + ($donorRegistrations->get($monthKey)->count ?? 0)
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
    
    /**
     * Get total donations received trend (monthly for last 6 months)
     * This shows total donations amount received by all charities each month
     */
    public function getDonationsTrend(Request $request)
    {
        $months = $request->input('months', 6);
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();
        
        // Get monthly donation totals (only confirmed donations)
        $donations = Donation::where('status', 'completed')
            ->where('is_refunded', false)
            ->where(function($q) use ($startDate) {
                $q->whereNotNull('donated_at')
                  ->where('donated_at', '>=', $startDate)
                  ->orWhere(function($q2) use ($startDate) {
                      $q2->whereNull('donated_at')
                         ->where('created_at', '>=', $startDate);
                  });
            })
            ->select(
                DB::raw('DATE_FORMAT(COALESCE(donated_at, created_at), "%Y-%m") as month'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy('month');
        
        // Generate data for all months
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $monthLabel = $date->format('M');
            
            $monthData = $donations->get($monthKey);
            
            $data[] = [
                'month' => $monthLabel,
                'amount' => $monthData ? (float) $monthData->total : 0,
                'count' => $monthData ? (int) $monthData->count : 0
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
    
    /**
     * Get combined dashboard chart data
     */
    public function getChartsData(Request $request)
    {
        $months = $request->input('months', 6);
        
        return response()->json([
            'success' => true,
            'registrations' => $this->getRegistrationsTrend($request)->getData()->data,
            'donations' => $this->getDonationsTrend($request)->getData()->data
        ]);
    }
}
