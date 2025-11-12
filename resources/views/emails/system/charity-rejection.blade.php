@extends('emails.layout')

@section('title', 'Charity Application Update')

@section('content')
    <h2>Charity Account Application Update</h2>
    
    <p>Dear {{ $userName }},</p>
    
    <div class="warning-box">
        <h3 style="margin-top: 0;">Application Status Update</h3>
        <p>Thank you for your interest in joining CharityHub. After careful review of your application for <strong>{{ $charityName }}</strong>, we regret to inform you that we are unable to approve your charity account at this time.</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">📋 Review Details:</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Organization:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $charityName }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Reviewed On:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $reviewedAt }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; color: #666;"><strong>Status:</strong></td>
                <td style="padding: 10px 0; text-align: right;">
                    <span style="background: #dc3545; color: white; padding: 3px 10px; border-radius: 4px; font-size: 12px;">
                        NOT APPROVED
                    </span>
                </td>
            </tr>
        </table>
    </div>
    
    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 25px 0;">
        <p style="margin: 0 0 10px 0; color: #856404; font-weight: bold;">Reason for Decision:</p>
        <p style="margin: 0; color: #856404;">{{ $reason }}</p>
    </div>
    
    <div class="info-box">
        <p><strong>🔍 Common Reasons for Non-Approval:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong>Incomplete Documentation:</strong> Missing required legal documents or registration certificates</li>
            <li><strong>Insufficient Information:</strong> Application lacks detailed organizational information</li>
            <li><strong>Verification Issues:</strong> Unable to verify organization's legal status or legitimacy</li>
            <li><strong>Eligibility Requirements:</strong> Organization doesn't meet our current criteria</li>
            <li><strong>Document Quality:</strong> Submitted documents are unclear or unreadable</li>
        </ul>
    </div>
    
    <div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 25px 0;">
        <p style="margin: 0 0 10px 0; color: #1565c0; font-weight: bold;">📝 Want to Reapply?</p>
        <p style="margin: 0; color: #424242;">We encourage you to address the issues mentioned above and submit a new application when ready. Here's what you need:</p>
        <ul style="margin: 10px 0; padding-left: 20px; color: #424242;">
            <li>Valid registration/incorporation documents</li>
            <li>Proof of tax-exempt status (if applicable)</li>
            <li>Detailed organizational information</li>
            <li>Clear mission statement and objectives</li>
            <li>Contact information and authorized representatives</li>
        </ul>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $resubmitUrl }}" class="button">Resubmit Application</a>
        <a href="{{ $guidelinesUrl }}" class="button" style="background: #6c757d;">View Charity Guidelines</a>
    </div>
    
    <div class="info-box">
        <p><strong>💡 Before Reapplying:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Review our charity registration guidelines carefully</li>
            <li>Ensure all required documents are complete and legible</li>
            <li>Verify your organization meets eligibility criteria</li>
            <li>Double-check all information for accuracy</li>
            <li>Prepare high-quality scans of all documents</li>
        </ul>
    </div>
    
    <div class="success-box">
        <p><strong>🤝 Need Assistance?</strong></p>
        <p>If you have questions about this decision or need help understanding what's required for approval, our support team is here to help!</p>
        <p style="margin: 10px 0 0 0;"><a href="{{ $supportUrl }}" style="color: #667eea; text-decoration: underline;">Contact Support Team</a></p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0; text-align: center;">
        <p style="margin: 0 0 10px 0; color: #666;">📞 <strong>Alternative Contact Methods:</strong></p>
        <p style="margin: 0; color: #666;">
            <strong>Email:</strong> support@charityhub.com<br>
            <strong>Hours:</strong> Monday-Friday, 9 AM - 6 PM PHT
        </p>
    </div>
    
    <p>We appreciate your interest in CharityHub and hope to work with your organization in the future once the requirements are met.</p>
    
    <p>Sincerely,<br>
    <strong>The CharityHub Verification Team</strong></p>
@endsection
