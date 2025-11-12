@extends('emails.layout')

@section('title', 'Test Email - CharityConnect')

@section('content')
    <h2>🎉 Test Email Successful!</h2>
    
    <p>Hello {{ $name }},</p>
    
    <div class="success-box">
        <strong>Great news!</strong> Your email system is working correctly.
    </div>
    
    <p>This is a test email from CharityConnect to verify that your email configuration is set up properly.</p>
    
    <p><strong>Email Details:</strong></p>
    <ul>
        <li><strong>Recipient:</strong> {{ $name }}</li>
        <li><strong>Timestamp:</strong> {{ now()->format('F d, Y h:i A') }}</li>
        <li><strong>System:</strong> CharityConnect Platform</li>
    </ul>
    
    <div class="info-box">
        <p><strong>What this means:</strong></p>
        <p>Your SMTP configuration is working, and emails from CharityConnect can now be sent successfully. This includes donation confirmations, verification emails, and notifications.</p>
    </div>
    
    <p>If you received this email in error, please disregard it.</p>
    
    <p>Best regards,<br>
    <strong>The CharityConnect Team</strong></p>
@endsection
