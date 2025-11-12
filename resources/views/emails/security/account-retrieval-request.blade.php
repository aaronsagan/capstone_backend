@extends('emails.layout')

@section('title', 'Account Reactivation Request')

@section('content')
    <h2>Account Reactivation Request Received</h2>
    
    <p>Dear User,</p>
    
    <div class="success-box">
        <h3 style="margin-top: 0;">✅ Request Submitted Successfully</h3>
        <p>We have received your request to reactivate your <strong>{{ $type }}</strong> account.</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">Request Details:</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Email:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $email }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Account Type:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $type }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; color: #666;"><strong>Submitted:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $submittedAt }}</td>
            </tr>
        </table>
    </div>
    
    @if($message)
    <div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 25px 0;">
        <p style="margin: 0 0 5px 0; color: #1565c0; font-weight: bold;">Your Message:</p>
        <p style="margin: 0; color: #424242;">{{ $message }}</p>
    </div>
    @endif
    
    <div class="info-box">
        <p><strong>📋 What Happens Next:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong>Review Process:</strong> Our team will review your request within 24-48 hours</li>
            <li><strong>Identity Verification:</strong> We may contact you for additional verification</li>
            <li><strong>Email Notification:</strong> You'll receive an update once your request is reviewed</li>
            <li><strong>Account Access:</strong> If approved, your account will be reactivated immediately</li>
        </ul>
    </div>
    
    <div class="warning-box">
        <p><strong>⚠️ Important Notes:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>This request is for <strong>account reactivation</strong> only</li>
            <li>If you didn't submit this request, please contact us immediately</li>
            <li>Previous account data and history will be restored upon approval</li>
            <li>Security measures may be applied based on your account history</li>
        </ul>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0; text-align: center;">
        <p style="margin: 0 0 10px 0; color: #666;">📞 <strong>Need Help?</strong></p>
        <p style="margin: 0; color: #666;">
            <strong>Email:</strong> support@charityhub.com<br>
            <strong>Hours:</strong> Monday-Friday, 9 AM - 6 PM PHT
        </p>
    </div>
    
    <p>We appreciate your patience during this process.</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Security Team</strong></p>
@endsection
