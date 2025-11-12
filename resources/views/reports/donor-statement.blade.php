@extends('reports.layouts.pdf')

@section('title', 'Donor Statement - ' . $donor['name'])

@section('report-title')
    Donation Statement
@endsection

@section('meta-info')
    <tr>
        <td>Donor Name:</td>
        <td><strong>{{ $donor['name'] }}</strong></td>
    </tr>
    <tr>
        <td>Email:</td>
        <td>{{ $donor['email'] }}</td>
    </tr>
    <tr>
        <td>Period:</td>
        <td>{{ $period['start'] }} to {{ $period['end'] }}</td>
    </tr>
    <tr>
        <td>Report Type:</td>
        <td>Comprehensive Donation History</td>
    </tr>
@endsection

@section('content')
    <div class="summary-box">
        <h3>Summary Statistics</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <span class="label">Total Donated</span>
                <span class="value">{{ \App\Helpers\ReportGenerator::formatCurrency($summary['total_donated']) }}</span>
            </div>
            <div class="summary-item">
                <span class="label">Total Donations</span>
                <span class="value">{{ $summary['total_count'] }}</span>
            </div>
            <div class="summary-item">
                <span class="label">Charities Supported</span>
                <span class="value">{{ $summary['charities_count'] }}</span>
            </div>
            <div class="summary-item">
                <span class="label">Avg. Donation</span>
                <span class="value">{{ \App\Helpers\ReportGenerator::formatCurrency($summary['average_donation']) }}</span>
            </div>
        </div>
    </div>
    
    <h2>Donation History</h2>
    
    @if(count($donations) > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 25%;">Charity</th>
                    <th style="width: 25%;">Campaign</th>
                    <th style="width: 13%;">Amount</th>
                    <th style="width: 12%;">Status</th>
                    <th style="width: 13%;">Receipt</th>
                </tr>
            </thead>
            <tbody>
                @foreach($donations as $donation)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($donation['date'])->format('M d, Y') }}</td>
                        <td>{{ $donation['charity'] }}</td>
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
                        <td style="font-size: 8pt;">{{ $donation['receipt_no'] ?? 'N/A' }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3" class="text-right"><strong>TOTAL:</strong></td>
                    <td class="amount text-right">{{ \App\Helpers\ReportGenerator::formatCurrency($summary['total_donated']) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="note">
            <p><strong>No donations found</strong> for the selected period.</p>
        </div>
    @endif
    
    @if(isset($by_charity) && count($by_charity) > 0)
        <h2>Breakdown by Charity</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">Rank</th>
                    <th style="width: 50%;">Charity Name</th>
                    <th style="width: 20%;">Total Amount</th>
                    <th style="width: 20%;">Donation Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($by_charity as $index => $charity)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $charity['name'] }}</td>
                        <td class="amount text-right">{{ \App\Helpers\ReportGenerator::formatCurrency($charity['total']) }}</td>
                        <td class="text-center">{{ $charity['count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    
    <div class="note">
        <p><strong>Important Notice:</strong></p>
        <p>This statement is provided for your records only. For tax purposes, please use the official receipts provided by each charity. CharityHub is a platform facilitating donations and does not process payments directly.</p>
    </div>
    
    <div style="margin-top: 40px; text-align: center; font-size: 9pt; color: #6b7280;">
        <p>Thank you for your generous support through CharityHub!</p>
        <p>Your donations are making a difference in communities across the Philippines.</p>
    </div>
@endsection
