@extends('emails.layout')

@section('title', 'Tax Information Updated')

@section('content')
    <h2>📋 Tax Information Updated Successfully</h2>
    
    <p>Dear {{ $userName }},</p>
    
    <div class="success-box">
        <h3 style="margin-top: 0;">Your tax information has been updated</h3>
        <p>We've successfully updated your tax and billing information in our system.</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">Updated Tax Information:</h3>
        <table style="width: 100%; border-collapse: collapse;">
            @if($tin !== 'N/A')
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>TIN (Tax ID):</strong></td>
                <td style="padding: 10px 0; text-align: right; font-family: monospace;">{{ $tin }}</td>
            </tr>
            @endif
            @if($businessName !== 'N/A')
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Business Name:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $businessName }}</td>
            </tr>
            @endif
            @if($address !== 'N/A')
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Billing Address:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $address }}</td>
            </tr>
            @endif
            <tr>
                <td style="padding: 10px 0; color: #666;"><strong>Updated On:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $timestamp }}</td>
            </tr>
        </table>
    </div>
    
    <div class="info-box">
        <p><strong>📄 Tax Documentation:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Your tax information is used for generating donation receipts</li>
            <li>This information helps ensure compliance with tax regulations</li>
            <li>Donation receipts will reflect your updated information</li>
            <li>Keep your information current for accurate tax records</li>
        </ul>
    </div>
    
    <div class="success-box">
        <p><strong>✅ Benefits of Updated Tax Info:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Accurate tax-deductible donation receipts</li>
            <li>Proper documentation for annual tax filing</li>
            <li>Compliance with Philippine tax regulations</li>
            <li>Easier tracking of charitable contributions</li>
        </ul>
    </div>
    
    <div class="warning-box">
        <p><strong>🔒 Security Notice:</strong></p>
        <p>If you did not make this change, please contact our support team immediately. Your account security is important to us.</p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $dashboardUrl }}" class="button">Review Tax Information</a>
    </div>
    
    <div class="info-box">
        <p><strong>💡 Need Help?</strong></p>
        <p>If you have questions about tax documentation or need assistance with your tax information, our support team is here to help!</p>
    </div>
    
    <p>Thank you for keeping your information up to date!</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Team</strong></p>
@endsection
