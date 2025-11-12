@extends('emails.layout')

@section('content')
<h2>✨ New Campaign from {{ $charityName }}</h2>

<p>Hi {{ $followerName }},</p>

<p>Great news! {{ $charityName }}, a charity you're following, has just launched a new campaign.</p>

<div class="info-box">
    <h3 style="margin-top: 0; color: #10b981;">{{ $campaignTitle }}</h3>
    <p>{{ $campaignDescription }}</p>
    
    <strong>Campaign Details:</strong><br>
    Goal Amount: ₱{{ $goalAmount }}<br>
    Campaign Ends: {{ $endDate }}<br>
    Charity: {{ $charityName }}
</div>

<p><strong>Why this campaign matters:</strong></p>
<p>{{ $charityName }} needs your support to make this initiative a reality. Every contribution, no matter the size, brings them closer to their goal.</p>

<div class="success-box">
    <strong>Be among the first supporters!</strong> Early contributions help build momentum and encourage others to donate. Your support can make all the difference!
</div>

<p style="text-align: center;">
    <a href="{{ $campaignUrl }}" class="button">View Campaign & Donate</a>
</p>

<p><strong>Want to learn more about {{ $charityName }}?</strong></p>
<ul>
    <li>View their profile and mission</li>
    <li>See their past campaigns and impact</li>
    <li>Read updates from beneficiaries</li>
</ul>

<p style="text-align: center;">
    <a href="{{ $charityUrl }}" style="color: #10b981; text-decoration: none;">Visit {{ $charityName }}'s Profile →</a>
</p>

<p>Thank you for being part of our community and supporting causes that matter!</p>

<p>Best regards,<br>
The CharityHub Team</p>

<p style="font-size: 11px; color: #999; margin-top: 20px;">
    You're receiving this because you follow {{ $charityName }}. <a href="{{ config('app.frontend_url') }}/settings/notifications" style="color: #10b981;">Manage your notification preferences</a>
</p>
@endsection
