@extends('emails.layout')

@section('content')
<h2>✅ Your Donation Has Been Verified!</h2>

<p>Hi {{ $donorName }},</p>

<p>Wonderful news! Your donation has been verified and officially recorded. Thank you for your generosity!</p>

<div class="success-box">
    <strong>Amount:</strong> ₱{{ $amount }}<br>
    <strong>Campaign:</strong> {{ $campaignTitle }}<br>
    <strong>Charity:</strong> {{ $charityName }}<br>
    <strong>Transaction ID:</strong> {{ $transactionId }}<br>
    <strong>Verified:</strong> {{ $verifiedDate }}
</div>

<p>Your contribution is now being put to work making a real difference. The charity will use these funds according to the campaign's stated goals.</p>

<div class="info-box">
    <strong>What happens next?</strong>
    <ul style="margin: 10px 0 0 0; padding-left: 20px;">
        <li>Your donation is now officially counted towards the campaign goal</li>
        <li>You'll receive updates on the campaign's progress</li>
        <li>You can view your receipt and donation history anytime</li>
        <li>Tax-deductible receipt will be available for download</li>
    </ul>
</div>

<p style="text-align: center;">
    <a href="{{ $dashboardUrl }}" class="button">View Donation History</a>
</p>

<p>Your kindness is transforming lives. Thank you for being part of the CharityHub community!</p>

<p>With gratitude,<br>
The CharityHub Team</p>
@endsection
