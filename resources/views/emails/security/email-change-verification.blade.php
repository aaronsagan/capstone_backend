@extends('emails.layout')

@section('title', 'Verify Your New Email')

@section('content')
    <h2>Verify Your New Email Address</h2>
    
    <p>Dear {{ $userName }},</p>
    
    <div class="info-box">
        <h3 style="margin-top: 0;">📧 Email Change Request</h3>
        <p>You recently requested to change your CharityHub account email address.</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Current Email:</strong></td>
                <td style="padding: 10px 0; text-align: right; color: #dc3545;">{{ $oldEmail }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; color: #666;"><strong>New Email:</strong></td>
                <td style="padding: 10px 0; text-align: right; color: #28a745; font-weight: bold;">{{ $newEmail }}</td>
            </tr>
        </table>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <p style="margin-bottom: 15px;">Click the button below to verify your new email address:</p>
        <a href="{{ $verificationUrl }}" class="button" style="font-size: 18px; padding: 15px 40px;">Verify New Email</a>
    </div>
    
    <div class="warning-box">
        <p><strong>⚠️ Important:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>This link will expire on <strong>{{ $expiresAt }}</strong></li>
            <li>Your current email ({{ $oldEmail }}) will remain active until you verify</li>
            <li>If you didn't request this change, <strong>ignore this email</strong> and contact support</li>
        </ul>
    </div>
    
    <div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 25px 0;">
        <p style="margin: 0 0 5px 0; color: #1565c0; font-weight: bold;">🔐 Security Tip:</p>
        <p style="margin: 0; color: #424242;">After changing your email, you'll need to log in using your new email address. Make sure to update any saved credentials.</p>
    </div>
    
    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 25px 0;">
        <p style="margin: 0 0 5px 0; color: #856404; font-weight: bold;">Can't click the button?</p>
        <p style="margin: 5px 0 0 0; color: #856404; font-size: 12px; word-break: break-all;">Copy and paste this link into your browser:</p>
        <p style="margin: 5px 0 0 0; color: #856404; font-size: 11px; word-break: break-all;">{{ $verificationUrl }}</p>
    </div>
    
    <p>If you have any questions or concerns, please contact our support team.</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Security Team</strong></p>
@endsection
