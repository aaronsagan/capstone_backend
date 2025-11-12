@extends('emails.layout')

@section('title', 'Verify Your Email')

@section('content')
    <h2>Welcome to CharityConnect, {{ $userName }}! 🎉</h2>
    
    <p>Thank you for joining our community! We're excited to have you on board.</p>
    
    <div class="success-box">
        <strong>One More Step!</strong>
        <p>Please verify your email address to activate your account and start making a difference.</p>
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
        <strong>⚠️ Security Note:</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>This verification link will expire in <strong>24 hours</strong></li>
            <li>If you didn't create this account, please ignore this email</li>
            <li>Never share this link with anyone</li>
        </ul>
    </div>
    
    <p><strong>What happens after verification?</strong></p>
    <ul>
        <li>Full access to your dashboard</li>
        <li>Ability to support charity campaigns</li>
        <li>Track your impact and donations</li>
        <li>Receive updates from charities you support</li>
    </ul>
    
    <p>If you need assistance, our support team is here to help!</p>
    
    <p>Best regards,<br>
    <strong>The CharityConnect Team</strong></p>
@endsection
