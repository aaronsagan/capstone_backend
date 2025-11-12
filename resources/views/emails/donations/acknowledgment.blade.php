@extends('emails.layout')

@section('content')
<div style="text-align: center; margin-bottom: 30px;">
    <div style="font-size: 48px; margin-bottom: 10px;">📜</div>
    <h1 style="color: #667eea; margin: 0;">Official Acknowledgment Letter</h1>
</div>

<p style="font-size: 16px; line-height: 1.6;">Dear <strong>{{ $donorName }}</strong>,</p>

<p style="font-size: 16px; line-height: 1.6;">
    Thank you for your generous contribution! We are pleased to provide you with an <strong>official acknowledgment letter</strong> 
    for your donation to <strong>{{ $charityName }}</strong>.
</p>

<!-- Donation Summary Box -->
<div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 30px 0; border-radius: 8px;">
    <h2 style="color: #667eea; margin-top: 0; font-size: 20px;">Donation Summary</h2>
    <table style="width: 100%; font-size: 14px;">
        <tr>
            <td style="padding: 8px 0;"><strong>Amount:</strong></td>
            <td style="padding: 8px 0; text-align: right; color: #667eea; font-size: 18px; font-weight: bold;">₱{{ $amount }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0;"><strong>Campaign/Purpose:</strong></td>
            <td style="padding: 8px 0; text-align: right;">{{ $campaignTitle }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0;"><strong>Receipt Number:</strong></td>
            <td style="padding: 8px 0; text-align: right; font-family: monospace;">{{ $receiptNumber }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0;"><strong>Transaction ID:</strong></td>
            <td style="padding: 8px 0; text-align: right; font-family: monospace;">{{ $transactionId }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0;"><strong>Date:</strong></td>
            <td style="padding: 8px 0; text-align: right;">{{ $donationDate }}</td>
        </tr>
    </table>
</div>

<!-- Tax Information -->
<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 8px;">
    <p style="margin: 0; font-size: 14px;">
        <strong>📋 Tax Information:</strong> Your official acknowledgment letter is attached to this email as a PDF. 
        This document can be used for tax deduction purposes. Please keep it for your records.
    </p>
</div>

<p style="font-size: 16px; line-height: 1.6;">
    <strong>What's Attached:</strong>
</p>
<ul style="font-size: 15px; line-height: 1.8;">
    <li>📄 Official Acknowledgment Letter (PDF)</li>
    <li>✅ Includes all donation details and charity information</li>
    <li>📋 Suitable for tax deduction purposes</li>
</ul>

<p style="font-size: 16px; line-height: 1.6;">
    Your generosity makes a real difference in our community. Thank you for choosing to support <strong>{{ $charityName }}</strong> 
    through CharityHub.
</p>

<!-- Call to Action -->
<div style="text-align: center; margin: 40px 0;">
    <a href="{{ $dashboardUrl }}" 
       style="display: inline-block; padding: 14px 28px; background-color: #667eea; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
        View Donation History
    </a>
</div>

<hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">

<p style="font-size: 14px; color: #6b7280; line-height: 1.6;">
    <strong>Need Help?</strong><br>
    If you have any questions about your donation or need additional documentation, please contact the charity directly 
    or reach out to our support team.
</p>

<div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: center;">
    <p style="margin: 0; font-size: 13px; color: #6b7280;">
        This is an automated email from CharityHub. The attached acknowledgment letter is an official document 
        for your donation records.
    </p>
</div>
@endsection
