@extends('reports.layouts.pdf')

@section('title', 'Charity Report - ' . $charity['name'])

@section('report-title')
    Charity Performance Report
@endsection

@section('meta-info')
    <tr>
        <td>Charity Name:</td>
        <td><strong>{{ $charity['name'] }}</strong></td>
    </tr>
    <tr>
        <td>Registration No:</td>
        <td>{{ $charity['reg_no'] ?? 'N/A' }}</td>
    </tr>
    <tr>
        <td>Period:</td>
        <td>{{ $period['start'] }} to {{ $period['end'] }}</td>
    </tr>
    <tr>
        <td>Report Type:</td>
        <td>Comprehensive Charity Report</td>
    </tr>
@endsection

@section('content')
    <div class="summary-box">
        <h3>Performance Summary</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <span class="label">Total Raised</span>
                <span class="value">{{ \App\Helpers\ReportGenerator::formatCurrency($summary['total_raised']) }}</span>
            </div>
            <div class="summary-item">
                <span class="label">Total Donations</span>
                <span class="value">{{ $summary['donation_count'] }}</span>
            </div>
            <div class="summary-item">
                <span class="label">Unique Donors</span>
                <span class="value">{{ $summary['unique_donors'] }}</span>
            </div>
            <div class="summary-item">
                <span class="label">Active Campaigns</span>
                <span class="value">{{ $summary['active_campaigns'] }}</span>
            </div>
        </div>
    </div>
    
    <h2>Received Donations</h2>
    
    @if(count($donations) > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 20%;">Donor</th>
                    <th style="width: 25%;">Campaign</th>
                    <th style="width: 13%;">Amount</th>
                    <th style="width: 12%;">Status</th>
                    <th style="width: 18%;">Reference</th>
                </tr>
            </thead>
            <tbody>
                @foreach($donations as $donation)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($donation['date'])->format('M d, Y') }}</td>
                        <td>{{ $donation['is_anonymous'] ? 'Anonymous' : $donation['donor'] }}</td>
                        <td>{{ $donation['campaign'] ?? 'General Donation' }}</td>
                        <td class="amount text-right">{{ \App\Helpers\ReportGenerator::formatCurrency($donation['amount']) }}</td>
                        <td>
                            @if($donation['status'] === 'completed')
                                <span class="badge badge-success">Completed</span>
                            @elseif($donation['status'] === 'pending')
                                <span class="badge badge-warning">Pending</span>
                            @else
                                <span class="badge badge-danger">{{ ucfirst($donation['status']) }}</span>
                            @endif
                        </td>
                        <td style="font-size: 8pt;">{{ $donation['reference'] ?? 'N/A' }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3" class="text-right"><strong>TOTAL RECEIVED:</strong></td>
                    <td class="amount text-right">{{ \App\Helpers\ReportGenerator::formatCurrency($summary['total_raised']) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="note">
            <p><strong>No donations found</strong> for the selected period.</p>
        </div>
    @endif
    
    @if(isset($top_donors) && count($top_donors) > 0)
        <h2>Top Donors</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">Rank</th>
                    <th style="width: 50%;">Donor Name</th>
                    <th style="width: 25%;">Total Donated</th>
                    <th style="width: 15%;">Donations</th>
                </tr>
            </thead>
            <tbody>
                @foreach($top_donors as $index => $donor)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $donor['name'] }}</td>
                        <td class="amount text-right">{{ \App\Helpers\ReportGenerator::formatCurrency($donor['total']) }}</td>
                        <td class="text-center">{{ $donor['count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    
    @if(isset($by_campaign) && count($by_campaign) > 0)
        <h2>Breakdown by Campaign</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Campaign Title</th>
                    <th style="width: 20%;">Total Raised</th>
                    <th style="width: 15%;">Donors</th>
                    <th style="width: 15%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($by_campaign as $campaign)
                    <tr>
                        <td>{{ $campaign['title'] }}</td>
                        <td class="amount text-right">{{ \App\Helpers\ReportGenerator::formatCurrency($campaign['total']) }}</td>
                        <td class="text-center">{{ $campaign['donors'] }}</td>
                        <td>
                            @if($campaign['status'] === 'published')
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-info">{{ ucfirst($campaign['status']) }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    
    @if(isset($monthly_trend) && count($monthly_trend) > 0)
        <h2>Monthly Donation Trend</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 40%;">Month</th>
                    <th style="width: 30%;">Amount Raised</th>
                    <th style="width: 30%;">Number of Donations</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthly_trend as $month)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($month['month'])->format('F Y') }}</td>
                        <td class="amount text-right">{{ \App\Helpers\ReportGenerator::formatCurrency($month['total']) }}</td>
                        <td class="text-center">{{ $month['count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    
    <div class="note">
        <p><strong>Financial Compliance:</strong></p>
        <p>This report is generated for your internal records and transparency requirements. Please maintain proper accounting records and issue official receipts to all donors as required by law.</p>
    </div>
    
    <div style="margin-top: 40px; text-align: center; font-size: 9pt; color: #6b7280;">
        <p>Continue your excellent work in serving the community!</p>
    </div>
@endsection
