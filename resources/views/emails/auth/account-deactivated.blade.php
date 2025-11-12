@extends('emails.layout')

@section('content')
<h2>🚧 Account Deactivated</h2>

<p>Hi {{ $userName }},</p>

<p>This email confirms that your CharityHub account has been deactivated as requested.</p>

<div class="info-box">
    <strong>Account Email:</strong> {{ $userEmail }}<br>
    <strong>Deactivated:</strong> {{ $deactivatedAt }}<br>
    <strong>Status:</strong> Temporarily Inactive
</div>

<p><strong>What this means:</strong></p>
<ul>
    <li>Your profile is no longer publicly visible</li>
    <li>You cannot log in to your account</li>
    <li>Your data is securely stored but not accessible</li>
    <li>Active donations and commitments are preserved</li>
</ul>

<div class="success-box">
    <strong>Good news!</strong> You can reactivate your account anytime within the next 90 days. After 90 days, your account and data may be permanently deleted.
</div>

<p><strong>To reactivate your account:</strong></p>
<ol>
    <li>Click the reactivation link below</li>
    <li>Verify your identity</li>
    <li>Confirm your decision to return</li>
    <li>Your account will be restored immediately</li>
</ol>

<p style="text-align: center;">
    <a href="{{ $reactivationUrl }}" class="button">Reactivate My Account</a>
</p>

<p>We're sorry to see you go. If there's anything we can do to improve your experience, please let us know!</p>

<p>Best regards,<br>
The CharityHub Team</p>
@endsection
