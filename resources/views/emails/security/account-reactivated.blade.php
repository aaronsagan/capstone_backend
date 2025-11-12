@extends('emails.layout')

@section('title', 'Account Reactivated')

@section('content')
    <h2>🎉 Account Reactivated Successfully</h2>
    
    <p>Dear {{ $userName }},</p>
    
    <div class="success-box">
        <h3 style="margin-top: 0;">✅ Welcome Back!</h3>
        <p>Your CharityHub account has been successfully reactivated on <strong>{{ $reactivatedAt }}</strong>.</p>
    </div>
    
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 8px; margin: 25px 0; text-align: center; color: white;">
        <p style="margin: 0; font-size: 48px;">🎊</p>
        <h3 style="margin: 10px 0; color: white;">Your Account is Now Active</h3>
        <p style="margin: 10px 0 0 0; opacity: 0.95;">You can now log in and access all CharityHub features</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">Account Details:</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Email:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $email }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Status:</strong></td>
                <td style="padding: 10px 0; text-align: right;">
                    <span style="background: #28a745; color: white; padding: 3px 10px; border-radius: 4px; font-size: 12px;">
                        ACTIVE
                    </span>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px 0; color: #666;"><strong>Reactivated:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $reactivatedAt }}</td>
            </tr>
        </table>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $loginUrl }}" class="button" style="font-size: 18px; padding: 15px 40px;">Log In to CharityHub</a>
    </div>
    
    <div class="info-box">
        <p><strong>✨ What You Can Do Now:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong>Access Your Dashboard:</strong> View your donation history and activity</li>
            <li><strong>Support Charities:</strong> Continue making a difference in your community</li>
            <li><strong>Manage Your Profile:</strong> Update your information and preferences</li>
            <li><strong>Track Your Impact:</strong> See the difference your donations are making</li>
        </ul>
    </div>
    
    <div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 25px 0;">
        <p style="margin: 0 0 5px 0; color: #1565c0; font-weight: bold;">🔐 Security Reminder:</p>
        <p style="margin: 0; color: #424242;">For your security, we recommend:</p>
        <ul style="margin: 10px 0; padding-left: 20px; color: #424242;">
            <li>Change your password if you haven't recently</li>
            <li>Enable Two-Factor Authentication for added security</li>
            <li>Review your account activity regularly</li>
            <li>Keep your contact information up to date</li>
        </ul>
    </div>
    
    <div class="success-box">
        <p><strong>🤝 Need Assistance?</strong></p>
        <p>Our support team is here to help! If you have any questions or need assistance, please don't hesitate to reach out.</p>
        <p style="margin: 10px 0 0 0;">
            <strong>Email:</strong> support@charityhub.com<br>
            <strong>Hours:</strong> Monday-Friday, 9 AM - 6 PM PHT
        </p>
    </div>
    
    <p>We're glad to have you back!</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Team</strong></p>
@endsection
