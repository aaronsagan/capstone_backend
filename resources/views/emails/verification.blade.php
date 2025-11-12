@extends('emails.layout')

@section('title', 'Verify Your Email - CharityConnect')

@section('content')
    <h2>Welcome to CharityConnect! 🎊</h2>
    
    <p>Hello {{ $name }},</p>
    
    <p>Thank you for registering with CharityConnect! We're excited to have you join our community of change-makers.</p>
    
    <p>To complete your registration and activate your account, please verify your email address by clicking the button below:</p>
    
    <div style="text-align: center;">
        <a href="{{ $verificationUrl }}" class="button">Verify Email Address</a>
    </div>
    
    <p>Or copy and paste this link into your browser:</p>
    <div class="info-box">
        <code style="word-break: break-all; font-size: 12px;">{{ $verificationUrl }}</code>
    </div>
    
    <div class="warning-box">
        <strong>⚠️ Security Note:</strong>
        <p>This verification link will expire in 24 hours. If you didn't create an account with CharityConnect, please ignore this email.</p>
    </div>
    
    <p>After verification, you'll be able to:</p>
    <ul>
        <li>Browse and support charity campaigns</li>
        <li>Track your donations</li>
        <li>Receive updates from charities you support</li>
        <li>Access your personalized dashboard</li>
    </ul>
    
    <p>Need help? Feel free to contact our support team.</p>
    
    <p>Best regards,<br>
    <strong>The CharityConnect Team</strong></p>
@endsection
