@extends('emails.layout')

@section('title', 'Reset Your Password')

@section('content')
    <h2>Password Reset Request 🔐</h2>
    
    <p>Hello {{ $userName }},</p>
    
    <p>We received a request to reset the password for your CharityConnect account.</p>
    
    <div class="info-box">
        <p><strong>Request Details:</strong></p>
        <ul style="margin: 5px 0; padding-left: 20px;">
            <li><strong>Email:</strong> {{ $userEmail }}</li>
            <li><strong>IP Address:</strong> {{ $ipAddress }}</li>
            <li><strong>Time:</strong> {{ now()->format('F d, Y h:i A') }}</li>
        </ul>
    </div>
    
    <p>Click the button below to reset your password:</p>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $resetUrl }}" class="button">Reset Password</a>
    </div>
    
    <p>Or copy and paste this link into your browser:</p>
    <div class="info-box">
        <code style="word-break: break-all; font-size: 12px;">{{ $resetUrl }}</code>
    </div>
    
    <div class="warning-box">
        <strong>⚠️ Important Security Information:</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>This password reset link will expire in <strong>{{ $expiresIn }} minutes</strong></li>
            <li>If you didn't request a password reset, <strong>please ignore this email</strong></li>
            <li>Your password will remain unchanged if you don't click the link</li>
            <li>Never share this link with anyone</li>
            <li>CharityConnect staff will never ask for your password</li>
        </ul>
    </div>
    
    <div class="info-box">
        <p><strong>Tips for creating a strong password:</strong></p>
        <ul style="margin: 5px 0; padding-left: 20px;">
            <li>Use at least 8 characters</li>
            <li>Include uppercase and lowercase letters</li>
            <li>Add numbers and special characters</li>
            <li>Avoid common words or personal information</li>
            <li>Don't reuse passwords from other sites</li>
        </ul>
    </div>
    
    <div class="warning-box">
        <strong>⚠️ Suspicious Activity?</strong>
        <p>If you didn't request this password reset, your account may be at risk. Please:</p>
        <ul style="margin: 5px 0; padding-left: 20px;">
            <li>Change your password immediately using a different method</li>
            <li>Review your account activity</li>
            <li>Enable two-factor authentication</li>
            <li>Contact our security team if needed</li>
        </ul>
    </div>
    
    <p>If you continue to have problems, please contact our support team.</p>
    
    <p>Stay secure,<br>
    <strong>The CharityConnect Security Team</strong></p>
@endsection
