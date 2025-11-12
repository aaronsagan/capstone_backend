@extends('emails.layout')

@section('title', 'Security Alert')

@section('content')
    <h2>🚨 Security Alert: Multiple Failed Login Attempts</h2>
    
    <p>Dear {{ $userName }},</p>
    
    <div class="warning-box">
        <h3 style="margin-top: 0;">⚠️ Unusual Activity Detected</h3>
        <p>We detected multiple failed login attempts on your CharityHub account. Your account security may be at risk.</p>
    </div>
    
    <div style="background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #856404;">Failed Login Details:</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #e0a800;">
                <td style="padding: 10px 0; color: #856404;"><strong>Number of Attempts:</strong></td>
                <td style="padding: 10px 0; text-align: right; color: #dc3545; font-weight: bold; font-size: 18px;">{{ $attempts }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #e0a800;">
                <td style="padding: 10px 0; color: #856404;"><strong>IP Address:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-family: monospace;">{{ $ipAddress }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; color: #856404;"><strong>Last Attempt:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $lastAttempt }}</td>
            </tr>
        </table>
    </div>
    
    <div style="background: #dc3545; color: white; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin: 0 0 10px 0; color: white;">🔒 Immediate Action Required</h3>
        <p style="margin: 0; opacity: 0.95;">If you did NOT attempt to log in, your account may be under attack. Please secure your account immediately.</p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $changePasswordUrl }}" class="button" style="font-size: 18px; padding: 15px 40px; background: #dc3545;">Change Password Now</a>
    </div>
    
    <div class="info-box">
        <p><strong>🛡️ Was This You?</strong></p>
        <p>If you were trying to log in and forgot your password:</p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Use the "Forgot Password" link on the login page</li>
            <li>Follow the password reset instructions sent to your email</li>
            <li>Make sure you're entering the correct email address</li>
        </ul>
    </div>
    
    <div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 25px 0;">
        <p style="margin: 0 0 10px 0; color: #1565c0; font-weight: bold;">🔐 Security Recommendations:</p>
        <ol style="margin: 0; padding-left: 20px; color: #424242;">
            <li><strong>Change Your Password:</strong> Create a strong, unique password</li>
            <li><strong>Enable 2FA:</strong> Add Two-Factor Authentication for extra security</li>
            <li><strong>Check Login History:</strong> Review recent account activity</li>
            <li><strong>Use Strong Passwords:</strong> Mix uppercase, lowercase, numbers, and symbols</li>
            <li><strong>Don't Reuse Passwords:</strong> Use a unique password for CharityHub</li>
        </ol>
    </div>
    
    <div class="warning-box">
        <p><strong>⚠️ If This Wasn't You:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong>Change your password immediately</strong> using the button above</li>
            <li><strong>Enable Two-Factor Authentication</strong> for added protection</li>
            <li><strong>Review your account activity</strong> for any unauthorized changes</li>
            <li><strong>Contact our security team</strong> if you notice anything suspicious</li>
        </ul>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0; text-align: center;">
        <p style="margin: 0 0 10px 0; color: #666;">📞 <strong>Need Immediate Assistance?</strong></p>
        <p style="margin: 0; color: #666;">
            <strong>Security Team:</strong> security@charityhub.com<br>
            <strong>Emergency Line:</strong> Available 24/7
        </p>
    </div>
    
    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 25px 0;">
        <p style="margin: 0; color: #856404;"><strong>Note:</strong> This alert was automatically generated to protect your account. If you have any questions or concerns, please contact our security team immediately.</p>
    </div>
    
    <p>Stay safe and secure!</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Security Team</strong></p>
@endsection
