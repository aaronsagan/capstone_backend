@extends('emails.layout')

@section('title', 'Two-Factor Authentication Enabled')

@section('content')
    <h2>🔐 Two-Factor Authentication Enabled</h2>
    
    <p>Dear {{ $userName }},</p>
    
    <div class="success-box">
        <h3 style="margin-top: 0;">✅ 2FA Activated Successfully</h3>
        <p>Two-Factor Authentication has been successfully enabled on your CharityHub account on <strong>{{ $enabledAt }}</strong>.</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">🔑 Your Recovery Codes</h3>
        <p style="color: #666;">These codes can be used to access your account if you lose access to your authenticator app. <strong>Save them securely!</strong></p>
        
        <div style="background: white; padding: 15px; border-radius: 4px; border: 2px dashed #667eea; margin-top: 15px;">
            @foreach($recoveryCodes as $code)
                <p style="margin: 5px 0; font-family: 'Courier New', monospace; font-size: 14px; color: #333;">{{ $code }}</p>
            @endforeach
        </div>
    </div>
    
    <div class="warning-box">
        <p><strong>⚠️ Important: Save Your Recovery Codes</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong>Store them securely:</strong> Keep these codes in a safe place (password manager, secure note, etc.)</li>
            <li><strong>Each code can only be used once:</strong> Once you use a recovery code, it becomes invalid</li>
            <li><strong>Don't share them:</strong> Never share these codes with anyone</li>
            <li><strong>Print or screenshot:</strong> Consider printing them or taking a secure screenshot</li>
        </ul>
    </div>
    
    <div class="info-box">
        <p><strong>🛡️ What This Means For You:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong>Enhanced Security:</strong> Your account is now protected with an extra layer of security</li>
            <li><strong>Login Process:</strong> You'll need to enter a code from your authenticator app when logging in</li>
            <li><strong>Trusted Devices:</strong> You can mark devices as trusted to skip 2FA temporarily</li>
            <li><strong>Recovery Options:</strong> Use recovery codes if you lose access to your authenticator</li>
        </ul>
    </div>
    
    <div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 25px 0;">
        <p style="margin: 0 0 10px 0; color: #1565c0; font-weight: bold;">📱 Next Time You Log In:</p>
        <ol style="margin: 0; padding-left: 20px; color: #424242;">
            <li>Enter your email and password</li>
            <li>Open your authenticator app</li>
            <li>Enter the 6-digit code shown in the app</li>
            <li>You're in!</li>
        </ol>
    </div>
    
    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 25px 0;">
        <p style="margin: 0; color: #856404;"><strong>Didn't enable 2FA?</strong> If you didn't activate Two-Factor Authentication, your account may be compromised. Please contact our security team immediately.</p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ config('app.frontend_url') }}/donor/settings" class="button">Manage Security Settings</a>
    </div>
    
    <p>Thank you for taking steps to secure your account!</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Security Team</strong></p>
@endsection
