@extends('emails.layout')

@section('title', 'Account Status Changed')

@section('content')
    @if($status === 'deactivated')
        <h2>Account Deactivated</h2>
        
        <p>Hello {{ $userName }},</p>
        
        <div class="warning-box">
            <h3 style="margin-top: 0;">Your account has been deactivated</h3>
            <p><strong>Deactivation Date:</strong> {{ $statusDate }}</p>
            @if($reason)
                <p><strong>Reason:</strong> {{ $reason }}</p>
            @endif
        </div>
        
        <p><strong>What this means:</strong></p>
        <ul>
            <li>You can no longer log in to your account</li>
            <li>Your profile is hidden from public view</li>
            <li>Active campaigns (if any) are paused</li>
            <li>You won't receive notifications</li>
        </ul>
        
        <div class="info-box">
            <p><strong>Want to come back?</strong></p>
            <p>Your account data is safely stored. You can request reactivation at any time by contacting support or using the reactivation link in your account settings.</p>
        </div>
        
    @elseif($status === 'reactivated')
        <h2>Welcome Back! Account Reactivated 🎉</h2>
        
        <p>Hello {{ $userName }},</p>
        
        <div class="success-box">
            <h3 style="margin-top: 0;">Your account has been reactivated!</h3>
            <p><strong>Reactivation Date:</strong> {{ $statusDate }}</p>
        </div>
        
        <p><strong>You now have full access to:</strong></p>
        <ul>
            <li>Your complete profile and dashboard</li>
            <li>All previous donation history</li>
            <li>Campaign management (for charities)</li>
            <li>Notifications and updates</li>
            <li>All CharityConnect features</li>
        </ul>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.frontend_url') }}/auth/login" class="button">Log In to Your Account</a>
        </div>
        
        <p>We're happy to have you back in our community!</p>
        
    @elseif($status === 'suspended')
        <h2>Account Suspended</h2>
        
        <p>Hello {{ $userName }},</p>
        
        <div class="warning-box">
            <h3 style="margin-top: 0;">⚠️ Your account has been suspended</h3>
            <p><strong>Suspension Date:</strong> {{ $statusDate }}</p>
            @if($reason)
                <p><strong>Reason:</strong> {{ $reason }}</p>
            @endif
        </div>
        
        <p><strong>What this means:</strong></p>
        <ul>
            <li>Your account access has been temporarily restricted</li>
            <li>You cannot log in until the suspension is lifted</li>
            <li>Your profile is not visible to other users</li>
            <li>All account activities are paused</li>
        </ul>
        
        <div class="info-box">
            <p><strong>Need clarification?</strong></p>
            <p>If you believe this suspension was made in error or would like to appeal, please contact our support team.</p>
        </div>
    @endif
    
    <div style="text-align: center; margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 8px;">
        <p style="margin: 0 0 10px 0;"><strong>Have questions?</strong></p>
        <a href="{{ $supportUrl }}" style="display: inline-block; padding: 10px 20px; background: #6c757d; color: white; border-radius: 5px; text-decoration: none;">Contact Support</a>
    </div>
    
    <p>Best regards,<br>
    <strong>The CharityConnect Team</strong></p>
@endsection
