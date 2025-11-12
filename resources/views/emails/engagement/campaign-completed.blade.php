@extends('emails.layout')

@section('content')
<h2>🎉 Campaign Goal Reached - Thank You!</h2>

<p>Dear {{ $donorName }},</p>

<p>We're thrilled to share some incredible news! Thanks to supporters like you, the campaign you donated to has reached its funding goal!</p>

<div class="success-box">
    <strong>Campaign:</strong> {{ $campaignTitle }}<br>
    <strong>Organized by:</strong> {{ $charityName }}<br>
    <strong>Goal Amount:</strong> ₱{{ $goalAmount }}<br>
    <strong>Total Raised:</strong> ₱{{ $totalRaised }}<br>
    <strong>Total Supporters:</strong> {{ $donorCount }} generous donors<br>
    <strong>Completed:</strong> {{ $completedDate }}
</div>

<p><strong>Your Impact:</strong></p>
<p>Your generous contribution was part of something amazing. Together with {{ $donorCount - 1 }} other donors, you've made this campaign a success! 🌟</p>

<div class="info-box">
    <strong>What happens next?</strong>
    <ul style="margin: 10px 0 0 0; padding-left: 20px;">
        <li>The charity will begin implementing the campaign goals</li>
        <li>You'll receive updates on the project's progress</li>
        <li>Impact reports will be shared with all supporters</li>
        <li>You can follow future campaigns from this charity</li>
    </ul>
</div>

<p style="text-align: center;">
    <a href="{{ $campaignUrl }}" class="button">View Campaign Updates</a>
</p>

<p><strong>From all of us at CharityHub and {{ $charityName }}:</strong></p>
<p style="font-size: 18px; color: #10b981; font-weight: 600; text-align: center;">
    THANK YOU! 💚
</p>

<p>Your generosity is transforming lives and making our world a better place. We're honored to have you as part of our community.</p>

<p>With heartfelt gratitude,<br>
The CharityHub Team & {{ $charityName }}</p>
@endsection
