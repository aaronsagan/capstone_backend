@extends('reports.layouts.pdf')

@section('title', 'CharityHub Platform Report')

@section('report-title')
    Platform Performance Report
@endsection

@section('meta-info')
    <tr>
        <td>Platform:</td>
        <td><strong>CharityHub - Philippine Charity Platform</strong></td>
    </tr>
    <tr>
        <td>Period:</td>
        <td>{{ $period['start'] }} to {{ $period['end'] }}</td>
    </tr>
    <tr>
        <td>Report Type:</td>
        <td>Comprehensive Platform Analytics</td>
    </tr>
    <tr>
        <td>Administrator:</td>
        <td>{{ $generated_by }}</td>
    </tr>
@endsection

@section('content')
    <div class="summary-box">
        <h3>Platform Overview</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <span class="label">Total Users</span>
                <span class="value">{{ number_format($stats['total_users']) }}</span>
            </div>
            <div class="summary-item">
                <span class="label">Verified Charities</span>
                <span class="value">{{ number_format($stats['verified_charities']) }}</span>
            </div>
            <div class="summary-item">
                <span class="label">Active Campaigns</span>
                <span class="value">{{ number_format($stats['active_campaigns']) }}</span>
            </div>
            <div class="summary-item">
                <span class="label">Total Raised</span>
                <span class="value">{{ \App\Helpers\ReportGenerator::formatCurrency($stats['total_raised']) }}</span>
            </div>
        </div>
    </div>
    
    <h2>User Statistics</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 40%;">User Type</th>
                <th style="width: 30%;">Count</th>
                <th style="width: 30%;">Percentage</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Donors</td>
                <td class="text-center">{{ number_format($stats['donors']) }}</td>
                <td class="text-center">{{ $stats['total_users'] > 0 ? number_format(($stats['donors'] / $stats['total_users']) * 100, 1) : 0 }}%</td>
            </tr>
            <tr>
                <td>Charity Administrators</td>
                <td class="text-center">{{ number_format($stats['charity_admins']) }}</td>
                <td class="text-center">{{ $stats['total_users'] > 0 ? number_format(($stats['charity_admins'] / $stats['total_users']) * 100, 1) : 0 }}%</td>
            </tr>
            <tr>
                <td>Active Users (Last 30 Days)</td>
                <td class="text-center">{{ number_format($stats['active_users']) }}</td>
                <td class="text-center">{{ $stats['total_users'] > 0 ? number_format(($stats['active_users'] / $stats['total_users']) * 100, 1) : 0 }}%</td>
            </tr>
            <tr class="total-row">
                <td><strong>TOTAL USERS</strong></td>
                <td class="text-center"><strong>{{ number_format($stats['total_users']) }}</strong></td>
                <td class="text-center"><strong>100%</strong></td>
            </tr>
        </tbody>
    </table>
    
    <h2>Charity Statistics</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 50%;">Status</th>
                <th style="width: 25%;">Count</th>
                <th style="width: 25%;">Percentage</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Approved & Active</td>
                <td class="text-center">{{ number_format($stats['verified_charities']) }}</td>
                <td class="text-center">{{ $stats['total_charities'] > 0 ? number_format(($stats['verified_charities'] / $stats['total_charities']) * 100, 1) : 0 }}%</td>
            </tr>
            <tr>
                <td>Pending Verification</td>
                <td class="text-center">{{ number_format($stats['pending_charities']) }}</td>
                <td class="text-center">{{ $stats['total_charities'] > 0 ? number_format(($stats['pending_charities'] / $stats['total_charities']) * 100, 1) : 0 }}%</td>
            </tr>
            <tr class="total-row">
                <td><strong>TOTAL CHARITIES</strong></td>
                <td class="text-center"><strong>{{ number_format($stats['total_charities']) }}</strong></td>
                <td class="text-center"><strong>100%</strong></td>
            </tr>
        </tbody>
    </table>
    
    <h2>Donation Statistics</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 50%;">Metric</th>
                <th style="width: 50%;">Value</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Donations Processed</td>
                <td class="amount text-right">{{ number_format($stats['total_donations']) }}</td>
            </tr>
            <tr>
                <td>Completed Donations</td>
                <td class="amount text-right">{{ number_format($stats['completed_donations']) }}</td>
            </tr>
            <tr>
                <td>Total Amount Raised</td>
                <td class="amount text-right">{{ \App\Helpers\ReportGenerator::formatCurrency($stats['total_raised']) }}</td>
            </tr>
            <tr>
                <td>Average Donation Amount</td>
                <td class="amount text-right">{{ \App\Helpers\ReportGenerator::formatCurrency($stats['average_donation']) }}</td>
            </tr>
            <tr>
                <td>Pending Donations</td>
                <td class="amount text-right">{{ number_format($stats['pending_donations']) }}</td>
            </tr>
        </tbody>
    </table>
    
    @if(isset($top_charities) && count($top_charities) > 0)
        <h2>Top 10 Charities by Donations</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">Rank</th>
                    <th style="width: 45%;">Charity Name</th>
                    <th style="width: 20%;">Total Raised</th>
                    <th style="width: 13%;">Donations</th>
                    <th style="width: 12%;">Campaigns</th>
                </tr>
            </thead>
            <tbody>
                @foreach($top_charities as $index => $charity)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $charity['name'] }}</td>
                        <td class="amount text-right">{{ \App\Helpers\ReportGenerator::formatCurrency($charity['total']) }}</td>
                        <td class="text-center">{{ $charity['donations'] }}</td>
                        <td class="text-center">{{ $charity['campaigns'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    
    @if(isset($monthly_trend) && count($monthly_trend) > 0)
        <div class="page-break"></div>
        <h2>Monthly Donation Trend</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 30%;">Month</th>
                    <th style="width: 25%;">Amount</th>
                    <th style="width: 20%;">Donations</th>
                    <th style="width: 25%;">Avg/Donation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthly_trend as $month)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($month['month'])->format('F Y') }}</td>
                        <td class="amount text-right">{{ \App\Helpers\ReportGenerator::formatCurrency($month['total']) }}</td>
                        <td class="text-center">{{ $month['count'] }}</td>
                        <td class="amount text-right">{{ \App\Helpers\ReportGenerator::formatCurrency($month['count'] > 0 ? $month['total'] / $month['count'] : 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    
    @if(isset($campaign_types) && count($campaign_types) > 0)
        <h2>Campaign Distribution by Type</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 40%;">Campaign Type</th>
                    <th style="width: 30%;">Total Campaigns</th>
                    <th style="width: 30%;">Total Raised</th>
                </tr>
            </thead>
            <tbody>
                @foreach($campaign_types as $type)
                    <tr>
                        <td>{{ $type['type'] }}</td>
                        <td class="text-center">{{ $type['count'] }}</td>
                        <td class="amount text-right">{{ \App\Helpers\ReportGenerator::formatCurrency($type['total']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    
    <div class="note">
        <p><strong>Report Disclaimer:</strong></p>
        <p>This report is generated for administrative and compliance purposes. All data is accurate as of the report generation time. CharityHub operates as a platform connecting donors with verified charities and does not handle fund transfers directly.</p>
    </div>
    
    <div style="margin-top: 40px; text-align: center; font-size: 9pt; color: #6b7280;">
        <p><strong>CharityHub Platform Report</strong></p>
        <p>Empowering Filipino Charities | Connecting Generous Donors | Building Stronger Communities</p>
    </div>
@endsection
