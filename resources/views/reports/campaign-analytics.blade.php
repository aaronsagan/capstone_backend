@extends('reports.layouts.pdf')

@section('title', 'Campaign Analytics Report')

@section('content')
    <!-- Header Section -->
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h1 style="margin: 0; color: #10b981; font-size: 24pt;">Campaign Analytics Report</h1>
                <h2 style="margin: 5px 0 0 0; color: #374151; font-size: 16pt;">{{ $charity['name'] }}</h2>
                @if(isset($charity['reg_no']))
                    <p style="margin: 5px 0; font-size: 9pt; color: #6b7280;">Registration No: {{ $charity['reg_no'] }}</p>
                @endif
            </div>
            <div style="text-align: right;">
                <p style="margin: 0; font-size: 9pt; color: #6b7280;">Generated: {{ \Carbon\Carbon::now()->format('F d, Y') }}</p>
                <p style="margin: 3px 0 0 0; font-size: 9pt; color: #6b7280;">
                    Report Period: {{ $period['start'] }} - {{ $period['end'] }}
                </p>
            </div>
        </div>
    </div>

    @if(!$has_campaigns)
        <!-- No Campaigns Message -->
        <div style="text-align: center; padding: 60px 20px; background: #f3f4f6; border-radius: 8px; margin: 40px 0;">
            <h2 style="color: #6b7280; font-size: 18pt; margin-bottom: 10px;">No Campaigns Available</h2>
            <p style="color: #9ca3af; font-size: 11pt;">{{ $message }}</p>
        </div>
    @else
        <!-- Summary Section -->
        <h2 style="color: #10b981; border-bottom: 2px solid #10b981; padding-bottom: 5px; margin-top: 20px; margin-bottom: 15px;">📊 Performance Summary</h2>
        <table style="width: 100%; margin-bottom: 25px; background: #f0fdf4; border: 2px solid #10b981;">
            <tr>
                <td style="width: 33.33%; padding: 15px; text-align: center; border-right: 1px solid #d1fae5;">
                    <p style="margin: 0; font-size: 10pt; color: #059669; font-weight: bold;">Total Campaigns</p>
                    <p style="margin: 8px 0 0 0; font-size: 24pt; font-weight: bold; color: #10b981;">{{ $summary['total_campaigns'] }}</p>
                </td>
                <td style="width: 33.33%; padding: 15px; text-align: center; border-right: 1px solid #d1fae5;">
                    <p style="margin: 0; font-size: 10pt; color: #059669; font-weight: bold;">Total Donations</p>
                    <p style="margin: 8px 0 0 0; font-size: 24pt; font-weight: bold; color: #10b981;">{{ number_format($summary['total_donations']) }}</p>
                </td>
                <td style="width: 33.33%; padding: 15px; text-align: center;">
                    <p style="margin: 0; font-size: 10pt; color: #059669; font-weight: bold;">Amount Raised</p>
                    <p style="margin: 8px 0 0 0; font-size: 24pt; font-weight: bold; color: #10b981;">{{ \App\Helpers\ReportGenerator::formatCurrency($summary['total_amount_raised']) }}</p>
                </td>
            </tr>
            <tr>
                <td colspan="3" style="padding: 15px; text-align: center; border-top: 1px solid #d1fae5;">
                    <p style="margin: 0; font-size: 10pt; color: #059669; font-weight: bold;">Average per Campaign</p>
                    <p style="margin: 5px 0 0 0; font-size: 16pt; font-weight: bold; color: #10b981;">{{ \App\Helpers\ReportGenerator::formatCurrency($summary['average_per_campaign']) }}</p>
                </td>
            </tr>
        </table>

        <!-- Top 3 Performing Campaigns -->
        @if(count($top_campaigns) > 0)
            <h2 style="color: #10b981; border-bottom: 2px solid #10b981; padding-bottom: 5px; margin-top: 30px; page-break-before: avoid;">🏆 Top Performing Campaigns</h2>
            <table style="width: 100%; margin-bottom: 25px; border-collapse: collapse;">
                @foreach($top_campaigns as $index => $campaign)
                    <tr style="background: {{ $index % 2 == 0 ? '#f0fdf4' : '#ffffff' }};">
                        <td style="width: 8%; padding: 12px; text-align: center; border: 1px solid #d1fae5;">
                            <strong style="background: #10b981; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11pt;">{{ $index + 1 }}</strong>
                        </td>
                        <td style="width: 42%; padding: 12px; border: 1px solid #d1fae5;">
                            <strong style="color: #374151; font-size: 11pt;">{{ $campaign['name'] }}</strong><br>
                            <span style="font-size: 9pt; color: #6b7280;">
                                {{ number_format($campaign['donor_count']) }} donors · 
                                {{ number_format($campaign['percent_funded'], 1) }}% funded
                            </span>
                        </td>
                        <td style="width: 25%; padding: 12px; text-align: right; border: 1px solid #d1fae5;">
                            <strong style="color: #10b981; font-size: 13pt;">{{ \App\Helpers\ReportGenerator::formatCurrency($campaign['raised']) }}</strong><br>
                            <span style="font-size: 8pt; color: #9ca3af;">of {{ \App\Helpers\ReportGenerator::formatCurrency($campaign['goal']) }}</span>
                        </td>
                        <td style="width: 25%; padding: 12px; border: 1px solid #d1fae5;">
                            <div style="width: 100%; background: #e5e7eb; height: 20px; position: relative; border-radius: 3px;">
                                <div style="position: absolute; left: 0; top: 0; height: 100%; width: {{ min($campaign['percent_funded'], 100) }}%; background: #10b981; border-radius: 3px;"></div>
                                <span style="position: absolute; left: 0; right: 0; text-align: center; line-height: 20px; font-size: 9pt; font-weight: bold; color: {{ $campaign['percent_funded'] > 50 ? '#ffffff' : '#374151' }};">
                                    {{ number_format($campaign['percent_funded'], 1) }}%
                                </span>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif

        <!-- Campaign Performance Table -->
        <h2 style="color: #10b981; border-bottom: 2px solid #10b981; padding-bottom: 5px; margin-top: 30px; page-break-before: auto;">📈 All Campaign Performance</h2>
        <table style="page-break-inside: avoid;">
            <thead>
                <tr>
                    <th style="width: 35%;">Campaign Name</th>
                    <th style="width: 15%;">Goal</th>
                    <th style="width: 15%;">Raised</th>
                    <th style="width: 12%;">% Funded</th>
                    <th style="width: 13%;">Donors</th>
                    <th style="width: 10%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($campaigns as $campaign)
                    <tr>
                        <td>{{ $campaign['name'] }}</td>
                        <td class="amount">{{ \App\Helpers\ReportGenerator::formatCurrency($campaign['goal']) }}</td>
                        <td class="amount" style="font-weight: bold; color: #10b981;">{{ \App\Helpers\ReportGenerator::formatCurrency($campaign['raised']) }}</td>
                        <td class="text-center">
                            <span style="background: {{ $campaign['percent_funded'] >= 100 ? '#10b981' : ($campaign['percent_funded'] >= 75 ? '#3b82f6' : '#f59e0b') }}; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 9pt;">
                                {{ number_format($campaign['percent_funded'], 1) }}%
                            </span>
                        </td>
                        <td class="text-center">{{ number_format($campaign['donor_count']) }}</td>
                        <td class="text-center">
                            <span style="font-size: 8pt; text-transform: uppercase; color: {{ $campaign['status'] === 'published' ? '#10b981' : '#6b7280' }};">
                                {{ $campaign['status'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td style="font-weight: bold;">TOTAL</td>
                    <td class="amount" style="font-weight: bold;">{{ \App\Helpers\ReportGenerator::formatCurrency(collect($campaigns)->sum('goal')) }}</td>
                    <td class="amount" style="font-weight: bold; color: #10b981;">{{ \App\Helpers\ReportGenerator::formatCurrency(collect($campaigns)->sum('raised')) }}</td>
                    <td class="text-center" style="font-weight: bold;">-</td>
                    <td class="text-center" style="font-weight: bold;">{{ number_format(collect($campaigns)->sum('donor_count')) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <!-- Milestone Achievements -->
        @if(count($milestones) > 0)
            <h2 style="color: #10b981; border-bottom: 2px solid #10b981; padding-bottom: 5px; margin-top: 30px;">🎯 Milestone Achievements</h2>
            <div style="background: #f0fdf4; padding: 15px; border-left: 4px solid #10b981; margin-bottom: 25px;">
                @foreach($milestones as $milestone)
                    <div style="margin-bottom: 10px;">
                        <p style="margin: 0; font-weight: bold; color: #374151; font-size: 11pt;">{{ $milestone['name'] }}</p>
                        <p style="margin: 3px 0 0 0; color: #10b981; font-size: 10pt;">
                            @foreach($milestone['achievements'] as $achievement)
                                ✓ {{ $achievement }}{{ !$loop->last ? ' · ' : '' }}
                            @endforeach
                        </p>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Monthly Donation Trend -->
        @if(count($monthly_trend) > 0)
            <h2 style="color: #10b981; border-bottom: 2px solid #10b981; padding-bottom: 5px; margin-top: 30px;">📅 Monthly Donation Trend</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 40%;">Month</th>
                        <th style="width: 35%;">Amount Raised</th>
                        <th style="width: 25%;">Donations</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($monthly_trend as $month)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($month['month'])->format('F Y') }}</td>
                            <td class="amount" style="font-weight: bold; color: #10b981;">{{ \App\Helpers\ReportGenerator::formatCurrency($month['total']) }}</td>
                            <td class="text-center">{{ number_format($month['count']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Visual Trend Chart -->
            <h3 style="margin: 20px 0 10px 0; color: #374151; font-size: 11pt;">📊 Visual Trend:</h3>
            <table style="width: 100%; border-collapse: collapse; background: #f9fafb;">
                @php
                    $maxAmount = collect($monthly_trend)->max('total');
                @endphp
                @foreach($monthly_trend as $month)
                    @php
                        $barWidth = $maxAmount > 0 ? ($month['total'] / $maxAmount) * 100 : 0;
                    @endphp
                    <tr>
                        <td style="width: 100px; padding: 8px; font-size: 9pt; color: #6b7280; font-weight: bold;">
                            {{ \Carbon\Carbon::parse($month['month'])->format('M Y') }}
                        </td>
                        <td style="padding: 8px;">
                            <div style="background: #e5e7eb; height: 24px; position: relative; border-radius: 3px;">
                                <div style="position: absolute; left: 0; top: 0; width: {{ $barWidth }}%; height: 100%; background: #10b981; border-radius: 3px;"></div>
                                <span style="position: absolute; left: 8px; line-height: 24px; font-size: 9pt; font-weight: bold; color: {{ $barWidth > 30 ? '#ffffff' : '#374151' }};">
                                    {{ \App\Helpers\ReportGenerator::formatCurrency($month['total']) }}
                                </span>
                            </div>
                        </td>
                        <td style="width: 80px; padding: 8px; text-align: right; font-size: 9pt; color: #6b7280;">
                            {{ number_format($month['count']) }} donations
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif

        <!-- Insights & Recommendations -->
        <div style="background: #eff6ff; padding: 15px; border-left: 4px solid #3b82f6; margin-top: 30px;">
            <h3 style="margin: 0 0 10px 0; color: #1e40af; font-size: 12pt;">💡 Key Insights</h3>
            <ul style="margin: 0; padding-left: 20px; color: #374151; font-size: 10pt; line-height: 1.6;">
                <li>Average funding per campaign: <strong>{{ number_format(($summary['total_campaigns'] > 0 ? $summary['average_per_campaign'] : 0), 2) }}</strong></li>
                <li>Total donor engagement: <strong>{{ number_format($summary['total_donations']) }} donations</strong> across all campaigns</li>
                @if(count($milestones) > 0)
                    <li><strong>{{ count($milestones) }} campaign(s)</strong> have reached significant milestones</li>
                @endif
                @if(count($top_campaigns) > 0 && $top_campaigns[0]['percent_funded'] >= 100)
                    <li>🎉 Your top campaign <strong>"{{ $top_campaigns[0]['name'] }}"</strong> has exceeded its goal!</li>
                @endif
            </ul>
        </div>
    @endif

    <!-- Footer -->
    <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #e5e7eb; text-align: center;">
        <p style="margin: 0; font-size: 9pt; color: #6b7280;">
            Generated automatically by <strong style="color: #10b981;">CharityHub Analytics System</strong>
        </p>
        <p style="margin: 5px 0 0 0; font-size: 8pt; color: #9ca3af;">
            This report contains confidential information for {{ $charity['name'] }} only.
        </p>
    </div>
@endsection
