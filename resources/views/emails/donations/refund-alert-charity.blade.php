@extends('emails.layout')

@section('title', 'Refund Request - Action Required')

@section('content')
    <h2>⚠️ Refund/Dispute Request Received - Action Required</h2>
    
    <p>Dear Charity Administrator,</p>
    
    <div class="warning-box">
        <h3 style="margin-top: 0;">A donor has requested a refund/dispute</h3>
        <p><strong>{{ $donorName }}</strong> has submitted a refund request for a donation to your organization.</p>
        <p><strong>Request Date:</strong> {{ $requestDate }}</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">Refund Request Details:</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Donor:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $donorName }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Campaign:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $campaignTitle }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Amount:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-weight: bold; color: #dc3545; font-size: 18px;">₱{{ $amount }}</td>
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
                <td style="padding: 10px 0; text-align: right; font-weight: bold; color: #dc3545;">{{ $refundReason }}</td>
            </tr>
        </table>
    </div>
    
    <div class="info-box">
        <p><strong>📋 Required Actions:</strong></p>
        <ol style="margin: 10px 0; padding-left: 20px;">
            <li>Review the refund request details carefully</li>
            <li>Log into your charity dashboard to see the full context</li>
            <li>Verify the donation record and payment details</li>
            <li>Make a decision: <strong>Approve</strong> or <strong>Deny</strong> the request</li>
            <li>Provide a clear reason for your decision</li>
            <li>Respond within <strong>3-5 business days</strong></li>
        </ol>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $dashboardUrl }}" class="button" style="background: #dc3545;">Review Refund Request</a>
    </div>
    
    <div class="warning-box">
        <p><strong>⚠️ Important Guidelines:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong>Timely Response:</strong> Please respond within 3-5 business days</li>
            <li><strong>Fair Review:</strong> Consider each request objectively and fairly</li>
            <li><strong>Clear Communication:</strong> Provide a detailed reason for your decision</li>
            <li><strong>Proper Documentation:</strong> Maintain records of all refund decisions</li>
            <li><strong>CharityHub Policy:</strong> Refund decisions must comply with platform policies</li>
        </ul>
    </div>
    
    <div class="info-box">
        <p><strong>💡 Tips for Handling Refund Requests:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Review your refund policy and ensure it's clearly communicated</li>
            <li>Check if the donation was used for the stated purpose</li>
            <li>Consider partial refunds if appropriate</li>
            <li>Reach out to the donor directly if clarification is needed</li>
            <li>Document all communications regarding the refund</li>
        </ul>
    </div>
    
    <p>If you have any questions about processing this refund request, please contact CharityHub support.</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Team</strong></p>
@endsection
