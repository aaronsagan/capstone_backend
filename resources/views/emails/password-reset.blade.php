@extends('emails.layout')

@section('title', 'Reset Your Password - CharityConnect')

@section('content')
    <h2>Password Reset Request 🔐</h2>
    
    <p>Hello {{ $name }},</p>
    
    <p>We received a request to reset the password for your CharityConnect account.</p>
    
    <p>Click the button below to reset your password:</p>
    
    <div style="text-align: center;">
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
            <li>If you didn't request a password reset, please ignore this email</li>
            <li>Your password will remain unchanged if you don't click the link</li>
            <li>Never share this link with anyone</li>
        </ul>
    </div>
    
    <p><strong>Didn't request this?</strong></p>
    <p>If you didn't request a password reset, your account may be at risk. Please contact our support team immediately to secure your account.</p>
    
    <p>Stay safe,<br>
    <strong>The CharityConnect Security Team</strong></p>
@endsection
