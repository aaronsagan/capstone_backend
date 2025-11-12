@extends('emails.layout')

@section('content')
<h2>🔒 Password Changed Successfully</h2>

<p>Hi {{ $userName }},</p>

<p>This email confirms that your CharityHub password was successfully changed.</p>

<div class="success-box">
    <strong>Changed At:</strong> {{ $changedAt }}<br>
    <strong>IP Address:</strong> {{ $ipAddress ?: 'Not available' }}<br>
    <strong>Device:</strong> {{ $device }}
</div>

<div class="warning-box">
    <strong>⚠️ Didn't make this change?</strong><br>
    If you did not change your password, your account may be compromised. Please take action immediately:
    <ol style="margin: 10px 0 0 0; padding-left: 20px;">
        <li>Reset your password immediately</li>
        <li>Review recent account activity</li>
        <li>Contact our support team</li>
    </ol>
</div>

<p><strong>Security Tips:</strong></p>
<ul>
    <li>✓ Use a strong, unique password</li>
    <li>✓ Enable two-factor authentication</li>
    <li>✓ Never share your password</li>
    <li>✓ Be cautious of phishing emails</li>
</ul>

@if($ipAddress)
<p style="text-align: center;">
    <a href="{{ $supportUrl }}" class="button">Contact Support</a>
</p>
@endif

<p>Your account security is our top priority. Thank you for keeping your account safe!</p>

<p>Best regards,<br>
The CharityHub Security Team</p>
@endsection
