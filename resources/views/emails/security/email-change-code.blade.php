@extends('emails.layout')

@section('title', 'Verify Your New Email Address')

@section('content')
    <h2>Email Change Verification</h2>
    
    <p>Hello {{ $userName }},</p>
    
    <p>You requested to change your email address on your CharityConnect account.</p>
    
    <div class="success-box">
        <strong>Your Verification Code</strong>
        <div style="text-align: center; margin: 20px 0;">
            <div style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #3b82f6; font-family: monospace;">
                {{ $code }}
            </div>
        </div>
        <p style="text-align: center; margin: 10px 0; color: #666;">
            Enter this code on the verification page to complete your email change.
        </p>
    </div>
    
    <div class="warning-box">
        <strong>⚠️ Important Security Information:</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>This code will expire in <strong>5 minutes</strong></li>
            <li>Your current email will remain active until you verify the new one</li>
            <li>Never share this code with anyone</li>
            <li>If you didn't request this change, please secure your account immediately</li>
        </ul>
    </div>
    
    <p><strong>What happens next?</strong></p>
    <ul>
        <li>Enter the 6-digit code on the verification page</li>
        <li>Your email will be updated instantly</li>
        <li>Use your new email for future logins</li>
        <li>You'll receive a confirmation once the change is complete</li>
    </ul>
    
    <p>If you need assistance, our support team is here to help!</p>
    
    <p>Best regards,<br>
    <strong>The CharityConnect Team</strong></p>
@endsection
