@extends('emails.layout')

@section('title', 'Account Reactivation Request')

@section('content')
    <h2>Account Reactivation Request Received</h2>
    
    <p>Hello {{ $userName }},</p>
    
    <p>We received a request to reactivate your donor account on CharityConnect.</p>
    
    <div class="info-box">
        <p><strong>Request Details:</strong></p>
        <ul style="margin: 5px 0; padding-left: 20px;">
            <li><strong>Email:</strong> {{ $userEmail }}</li>
            <li><strong>Request Date:</strong> {{ $requestDate }}</li>
            <li><strong>Account Type:</strong> Donor</li>
        </ul>
    </div>
    
    <p>To confirm and reactivate your account, please click the button below:</p>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $reactivateUrl }}" class="button">Reactivate My Account</a>
    </div>
    
    <p>Or copy and paste this link into your browser:</p>
    <div class="info-box">
        <code style="word-break: break-all; font-size: 12px;">{{ $reactivateUrl }}</code>
    </div>
    
    <div class="success-box">
        <p><strong>What happens after reactivation?</strong></p>
        <ul style="margin: 5px 0; padding-left: 20px;">
            <li>Your account will be fully restored</li>
            <li>You'll regain access to your donation history</li>
            <li>You can continue supporting charities</li>
            <li>All your previous data will be available</li>
        </ul>
    </div>
    
    <div class="warning-box">
        <strong>⚠️ Didn't Request This?</strong>
        <p>If you didn't request account reactivation, please ignore this email or contact support if you have concerns.</p>
    </div>
    
    <p>This reactivation link will expire in 48 hours.</p>
    
    <p>We're happy to have you back!<br>
    <strong>The CharityConnect Team</strong></p>
@endsection
