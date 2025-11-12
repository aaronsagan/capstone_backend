@extends('emails.layout')

@section('title', 'Charity Account Reactivation')

@section('content')
    <h2>Charity Account Reactivation Request</h2>
    
    <p>Hello {{ $userName }},</p>
    
    <p>We received a request to reactivate the charity account for <strong>{{ $organizationName }}</strong>.</p>
    
    <div class="info-box">
        <p><strong>Request Details:</strong></p>
        <ul style="margin: 5px 0; padding-left: 20px;">
            <li><strong>Organization:</strong> {{ $organizationName }}</li>
            <li><strong>Email:</strong> {{ $userEmail }}</li>
            <li><strong>Request Date:</strong> {{ $requestDate }}</li>
            <li><strong>Account Type:</strong> Charity Admin</li>
        </ul>
    </div>
    
    <p>To confirm and reactivate your charity account, please click the button below:</p>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $reactivateUrl }}" class="button">Reactivate Charity Account</a>
    </div>
    
    <p>Or copy and paste this link into your browser:</p>
    <div class="info-box">
        <code style="word-break: break-all; font-size: 12px;">{{ $reactivateUrl }}</code>
    </div>
    
    <div class="success-box">
        <p><strong>What happens after reactivation?</strong></p>
        <ul style="margin: 5px 0; padding-left: 20px;">
            <li>Your charity profile will be restored</li>
            <li>All campaigns will be reactivated</li>
            <li>You can accept new donations</li>
            <li>Access to your full dashboard and analytics</li>
            <li>Ability to post updates and engage donors</li>
        </ul>
    </div>
    
    <div class="warning-box">
        <strong>⚠️ Important:</strong>
        <p>After reactivation, your charity profile may need to go through verification again before accepting donations.</p>
    </div>
    
    <p>This reactivation link will expire in 48 hours.</p>
    
    <p>Welcome back to CharityConnect!<br>
    <strong>The CharityConnect Team</strong></p>
@endsection
