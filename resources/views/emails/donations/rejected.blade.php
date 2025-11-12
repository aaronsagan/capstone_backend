@extends('emails.layout')

@section('content')
<h2>⚠️ Donation Proof Requires Resubmission</h2>

<p>Hi {{ $donorName }},</p>

<p>We've reviewed your donation submission, but unfortunately we need additional information to verify your donation.</p>

<div class="warning-box">
    <strong>Donation Details:</strong><br>
    Amount: ₱{{ $amount }}<br>
    Campaign: {{ $campaignTitle }}<br>
    Charity: {{ $charityName }}<br>
    Transaction ID: {{ $transactionId }}
</div>

<div class="info-box">
    <strong>Reason for Rejection:</strong><br>
    {{ $reason }}
</div>

<p><strong>What you need to do:</strong></p>
<ol>
    <li>Locate your original proof of payment (receipt, screenshot, or transaction confirmation)</li>
    <li>Ensure the image is clear and shows:
        <ul>
            <li>Transaction date and time</li>
            <li>Amount paid</li>
            <li>Reference/transaction number</li>
            <li>Recipient details</li>
        </ul>
    </li>
    <li>Resubmit the proof through your donation history</li>
</ol>

<p style="text-align: center;">
    <a href="{{ $resubmitUrl }}" class="button">Resubmit Proof of Donation</a>
</p>

<p>We appreciate your patience. If you have any questions or need assistance, please don't hesitate to contact the charity directly or reach out to our support team.</p>

<p>Thank you for your continued support!</p>

<p>Best regards,<br>
The CharityHub Team</p>
@endsection
