@extends('emails.layout')

@section('title', 'Security Alert - CharityConnect')

@section('content')
    <h2>🔒 Security Alert: Multiple Failed Login Attempts</h2>
    
    <p>Hello {{ $userName }},</p>
    
    <div class="warning-box">
        <h3 style="margin-top: 0;">⚠️ Unusual Activity Detected</h3>
        <p>We detected multiple failed login attempts on your CharityConnect account.</p>
    </div>
    
    <p><strong>Activity Details:</strong></p>
    <ul>
        <li><strong>Failed Attempts:</strong> {{ $attemptCount }}</li>
        <li><strong>IP Address:</strong> {{ $ipAddress }}</li>
        <li><strong>Account Locked Until:</strong> {{ $lockUntil }}</li>
        <li><strong>Lockout Duration:</strong> {{ $lockoutMinutes }} minutes</li>
    </ul>
    
    <div class="info-box">
        <p><strong>What this means:</strong></p>
        <p>Your account has been temporarily locked to protect it from unauthorized access. The lock will automatically expire at the time shown above.</p>
    </div>
    
    <p><strong>Was this you?</strong></p>
    <p>If you were trying to log in and forgot your password, you can reset it using the button below:</p>
    
    <div style="text-align: center;">
        <a href="{{ $resetPasswordUrl }}" class="button">Reset Password</a>
    </div>
    
    <div class="warning-box">
        <p><strong>⚠️ Suspicious Activity?</strong></p>
        <p>If you didn't attempt to log in, your account may be at risk. Please:</p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Reset your password immediately</li>
            <li>Review your account activity</li>
            <li>Enable two-factor authentication if available</li>
            <li>Contact our security team if needed</li>
        </ul>
    </div>
    
    <p><strong>After the lockout expires:</strong></p>
    <ul>
        <li>You'll regain access to your account</li>
        <li>Make sure to use the correct password</li>
        <li>Consider updating your password for security</li>
    </ul>
    
    <p>We take account security seriously and apologize for any inconvenience.</p>
    
    <p>Stay secure,<br>
    <strong>The CharityConnect Security Team</strong></p>
@endsection
