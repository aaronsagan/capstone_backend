@extends('emails.layout')

@section('title', 'Refund Request Confirmation')

@section('content')
    <h2>Refund/Dispute Request Received</h2>
    
    <p>Dear {{ $donorName }},</p>
    
    <div class="info-box">
        <h3 style="margin-top: 0;">Your refund request has been submitted</h3>
        <p>We have received your refund/dispute request for your donation to <strong>{{ $charityName }}</strong>.</p>
        <p><strong>Request Date:</strong> {{ $requestDate }}</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">Donation & Refund Details:</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Campaign:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $campaignTitle }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Organization:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $charityName }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Amount:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-weight: bold; color: #dc3545;">₱{{ $amount }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Transaction ID:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-family: monospace;">{{ $transactionId }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Original Date:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $donationDate }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; color: #666;"><strong>Reason:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $refundReason }}</td>
            </tr>
        </table>
    </div>
    
    <div class="info-box">
        <p><strong>📋 What Happens Next:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Your refund request has been forwarded to {{ $charityName }}</li>
            <li>The charity will review your request and respond directly</li>
            <li>You'll receive an email notification once they make a decision</li>
            <li>If approved, {{ $charityName }} will process the refund directly to your original payment method</li>
            <li>You can track your refund status in your donation dashboard</li>
        </ul>
    </div>
    
    <div class="warning-box">
        <p><strong>⚠️ Important Information:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Refunds are handled directly between you and {{ $charityName }}</li>
            <li>CharityHub does not process refunds – the charity handles all refund requests</li>
            <li>Refund approvals are at the sole discretion of the charity organization</li>
            <li>For questions about your refund, you may contact the charity directly through CharityHub</li>
        </ul>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $dashboardUrl }}" class="button">View Refund Status</a>
    </div>
    
    <div class="info-box">
        <p><strong>📞 Need Help?</strong></p>
        <p>If you have any questions about your refund request, please don't hesitate to contact our support team. We're here to help!</p>
    </div>
    
    <p>Thank you for your patience as we process your request.</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Team</strong></p>
@endsection
