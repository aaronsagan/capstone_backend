@extends('emails.layout')

@section('title', 'Scheduled Maintenance Notice')

@section('content')
    <h2>⚠️ Scheduled Maintenance Notice</h2>
    
    <p>Dear {{ $userName }},</p>
    
    <div class="warning-box">
        <h3 style="margin-top: 0;">{{ $title }}</h3>
        <p>CharityHub will undergo scheduled maintenance to improve our services and ensure a better experience for you.</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">Maintenance Details:</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Start Time:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-weight: bold;">{{ $startTime }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>End Time:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-weight: bold;">{{ $endTime }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; color: #666;"><strong>Expected Duration:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-weight: bold; color: #dc3545;">{{ $duration }}</td>
            </tr>
        </table>
    </div>
    
    @if($message)
    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 25px 0;">
        <p style="margin: 0; color: #856404;"><strong>Additional Information:</strong></p>
        <p style="margin: 10px 0 0 0; color: #856404;">{{ $message }}</p>
    </div>
    @endif
    
    <div style="background: #dc3545; color: white; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin: 0 0 10px 0; color: white;">⚠️ Impact Notice</h3>
        <p style="margin: 0; opacity: 0.95;">During this maintenance window:</p>
        <ul style="margin: 10px 0 0 20px; padding: 0; opacity: 0.95;">
            <li>Donations will be temporarily unavailable</li>
            <li>Account login may be interrupted</li>
            <li>Campaign creation and editing will be disabled</li>
            <li>Email notifications may be delayed</li>
        </ul>
    </div>
    
    <div class="info-box">
        <p><strong>🔧 What We're Doing:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Upgrading system infrastructure</li>
            <li>Implementing new features and improvements</li>
            <li>Enhancing security measures</li>
            <li>Optimizing performance and reliability</li>
        </ul>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $statusPageUrl }}" class="button">View Status Page</a>
        <a href="{{ $homeUrl }}" class="button" style="background: #6c757d;">Visit CharityHub</a>
    </div>
    
    <div class="success-box">
        <p><strong>✅ After Maintenance:</strong></p>
        <p>Once maintenance is complete, all services will be restored. We'll send you a notification when CharityHub is back online.</p>
    </div>
    
    <p>We apologize for any inconvenience and appreciate your patience as we work to improve CharityHub!</p>
    
    <p>Thank you for your understanding,<br>
    <strong>The CharityHub Team</strong></p>
@endsection
