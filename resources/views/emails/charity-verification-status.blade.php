@extends('emails.layout')

@section('title', 'Charity Verification Status - CharityConnect')

@section('content')
    @if($status === 'approved')
        <h2>🎉 Congratulations! Your Charity Has Been Verified</h2>
        
        <p>Dear {{ $name }},</p>
        
        <div class="success-box">
            <h3 style="margin-top: 0;">Verification Approved ✅</h3>
            <p>Your charity organization has successfully passed our verification process!</p>
        </div>
        
        <p>You can now:</p>
        <ul>
            <li>Create and publish fundraising campaigns</li>
            <li>Receive donations from our community</li>
            <li>Access advanced charity management tools</li>
            <li>Post updates to engage with donors</li>
            <li>Generate transparency reports</li>
        </ul>
        
        <div style="text-align: center;">
            <a href="{{ $dashboardUrl }}" class="button">Go to Dashboard</a>
        </div>
        
        <p>Welcome to the CharityConnect verified community!</p>
        
    @else
        <h2>Charity Verification Update</h2>
        
        <p>Dear {{ $name }},</p>
        
        <div class="warning-box">
            <h3 style="margin-top: 0;">Verification Status: {{ ucfirst($status) }}</h3>
            <p>We've reviewed your charity verification request.</p>
        </div>
        
        @if($rejectionReason)
            <p><strong>Reason:</strong></p>
            <div class="info-box">
                {{ $rejectionReason }}
            </div>
            
            <p><strong>What you can do:</strong></p>
            <ul>
                <li>Review the feedback provided above</li>
                <li>Update your documentation accordingly</li>
                <li>Resubmit your verification request</li>
                <li>Contact support if you need assistance</li>
            </ul>
        @endif
        
        <div style="text-align: center;">
            <a href="{{ $dashboardUrl }}" class="button">View Details</a>
        </div>
    @endif
    
    <p>If you have any questions, please don't hesitate to contact our verification team.</p>
    
    <p>Best regards,<br>
    <strong>The CharityConnect Verification Team</strong></p>
@endsection
