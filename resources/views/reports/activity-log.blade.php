@extends('reports.layouts.pdf')

@section('title', 'Activity Log Report')

@section('report-title')
    User Activity Log Report
@endsection

@section('meta-info')
    <tr>
        <td>Period:</td>
        <td>{{ $period['start'] }} to {{ $period['end'] }}</td>
    </tr>
    <tr>
        <td>Total Activities:</td>
        <td><strong>{{ number_format($total_activities) }}</strong></td>
    </tr>
    @if(isset($filters['action_type']) && $filters['action_type'] !== 'all')
        <tr>
            <td>Filter - Action Type:</td>
            <td>{{ ucwords(str_replace('_', ' ', $filters['action_type'])) }}</td>
        </tr>
    @endif
    @if(isset($filters['user_role']) && $filters['user_role'] !== 'all')
        <tr>
            <td>Filter - User Role:</td>
            <td>{{ ucwords($filters['user_role']) }}</td>
        </tr>
    @endif
@endsection

@section('content')
    <div class="summary-box">
        <h3>Activity Summary</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <span class="label">Total Actions</span>
                <span class="value">{{ number_format($total_activities) }}</span>
            </div>
            <div class="summary-item">
                <span class="label">Unique Users</span>
                <span class="value">{{ number_format($unique_users) }}</span>
            </div>
            <div class="summary-item">
                <span class="label">Login Events</span>
                <span class="value">{{ number_format($login_count) }}</span>
            </div>
            <div class="summary-item">
                <span class="label">Donations Made</span>
                <span class="value">{{ number_format($donation_count) }}</span>
            </div>
        </div>
    </div>
    
    <h2>Activity Log Details</h2>
    
    @if(count($activities) > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 20%;">User</th>
                    <th style="width: 10%;">Role</th>
                    <th style="width: 18%;">Action</th>
                    <th style="width: 28%;">Description</th>
                    <th style="width: 12%;">IP Address</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activities as $activity)
                    <tr>
                        <td style="font-size: 8pt;">{{ \Carbon\Carbon::parse($activity['created_at'])->format('M d, H:i') }}</td>
                        <td>{{ $activity['user_name'] }}</td>
                        <td>
                            @if($activity['user_role'] === 'donor')
                                <span class="badge badge-info">Donor</span>
                            @elseif($activity['user_role'] === 'charity_admin')
                                <span class="badge badge-success">Charity</span>
                            @else
                                <span class="badge">{{ ucfirst($activity['user_role']) }}</span>
                            @endif
                        </td>
                        <td style="font-size: 8pt;">{{ ucwords(str_replace('_', ' ', $activity['action'])) }}</td>
                        <td style="font-size: 8pt;">{{ $activity['description'] }}</td>
                        <td style="font-size: 7pt;">{{ $activity['ip_address'] ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="note">
            <p><strong>No activities found</strong> matching the selected filters.</p>
        </div>
    @endif
    
    @if(isset($by_action) && count($by_action) > 0)
        <h2>Activity Breakdown by Action Type</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 60%;">Action Type</th>
                    <th style="width: 40%;">Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($by_action as $action)
                    <tr>
                        <td>{{ ucwords(str_replace('_', ' ', $action['action'])) }}</td>
                        <td class="text-center"><strong>{{ number_format($action['count']) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    
    <div class="note">
        <p><strong>Security Notice:</strong></p>
        <p>This activity log is maintained for security, auditing, and compliance purposes. All user actions are tracked with IP addresses and timestamps. Unauthorized access or suspicious activities are flagged for review.</p>
    </div>
@endsection
