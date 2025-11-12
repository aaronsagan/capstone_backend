@extends('emails.layout')

@section('title', 'Resend Email Verification')

@section('content')
    <h2>Email Verification Resent</h2>
    
    <p>Hello {{ $userName }},</p>
    
    <p>You requested a new verification link for your CharityConnect account.</p>
    
    <div class="info-box">
        <p><strong>Note:</strong> Any previous verification links have been invalidated. Please use the new link below.</p>
    </div>
    
    <p>Click the button below to verify your email address:</p>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $verifyUrl }}" class="button">Verify Email Address</a>
    </div>
    
    <p>Or copy and paste this link into your browser:</p>
    <div class="info-box">
        <code style="word-break: break-all; font-size: 12px;">{{ $verifyUrl }}</code>
    </div>
    
    <div class="warning-box">
        <strong>⚠️ Didn't Request This?</strong>
        <p>If you didn't request a new verification link, please contact our support team immediately.</p>
    </div>
    
    <p>This link will expire in 24 hours for security purposes.</p>
    
    <p>Best regards,<br>
    <strong>The CharityConnect Team</strong></p>
@endsection
