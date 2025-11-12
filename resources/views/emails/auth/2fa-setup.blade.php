@extends('emails.layout')

@section('title', '2FA Setup Confirmation')

@section('content')
    <h2>Two-Factor Authentication Enabled 🔐</h2>
    
    <p>Hello {{ $userName }},</p>
    
    <div class="success-box">
        <h3 style="margin-top: 0;">2FA Successfully Enabled!</h3>
        <p>Your account security has been enhanced with two-factor authentication.</p>
        <p><strong>Setup Date:</strong> {{ $setupDate }}</p>
    </div>
    
    <p>Two-factor authentication adds an extra layer of security to your CharityConnect account. You'll now need both your password and a verification code to log in.</p>
    
    <div class="warning-box">
        <h3 style="margin-top: 0;">⚠️ Important: Save Your Backup Codes</h3>
        <p><strong>Store these backup codes in a safe place!</strong> You can use them to access your account if you lose your authentication device.</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #333;">Your Backup Codes:</h3>
        <div style="font-family: monospace; font-size: 14px; line-height: 2;">
            @foreach($backupCodes as $code)
                <div style="background: white; padding: 10px; margin: 5px 0; border-radius: 4px; border: 1px solid #ddd;">
                    {{ $code }}
                </div>
            @endforeach
        </div>
    </div>
    
    <div class="info-box">
        <p><strong>📝 Backup Code Best Practices:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Print this email and store it securely</li>
            <li>Save codes in a password manager</li>
            <li>Each code can only be used once</li>
            <li>Never share these codes with anyone</li>
            <li>Keep them separate from your password</li>
        </ul>
    </div>
    
    <div class="success-box">
        <p><strong>✅ Your account is now more secure!</strong></p>
        <p>Next time you log in, you'll need to enter a verification code from your authenticator app.</p>
    </div>
    
    <div class="warning-box">
        <strong>⚠️ Lost Your Device?</strong>
        <p>If you lose access to your authentication device, use one of the backup codes above to log in. Then you can disable 2FA and set it up again with a new device.</p>
    </div>
    
    <p>If you didn't enable 2FA on your account, please contact support immediately.</p>
    
    <p>Stay secure,<br>
    <strong>The CharityConnect Security Team</strong></p>
@endsection
