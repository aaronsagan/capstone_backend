@extends('emails.layout')

@section('content')
<h2>✅ Email Verified Successfully!</h2>

<p>Hi {{ $userName }},</p>

<p>Great news! Your email address has been successfully verified. Your CharityHub account is now fully activated.</p>

<div class="success-box">
    <strong>Account Status:</strong> ✓ Verified<br>
    <strong>Account Type:</strong> {{ ucfirst(str_replace('_', ' ', $userRole)) }}<br>
    <strong>Verified At:</strong> {{ now()->format('F d, Y h:i A') }}
</div>

<p>You can now access all features of your CharityHub account:</p>

<ul>
    @if($userRole === 'donor')
    <li>Browse and support campaigns</li>
    <li>Track your donation history</li>
    <li>Follow your favorite charities</li>
    <li>Receive updates on campaigns you support</li>
    @elseif($userRole === 'charity_admin')
    <li>Create and manage campaigns</li>
    <li>Track donations and supporters</li>
    <li>Post updates to your followers</li>
    <li>Access analytics and reports</li>
    @else
    <li>Manage platform operations</li>
    <li>Monitor charities and campaigns</li>
    <li>Access system reports</li>
    @endif
</ul>

<p style="text-align: center;">
    <a href="{{ $dashboardUrl }}" class="button">Go to Dashboard</a>
</p>

<p>Thank you for joining CharityHub. Together, we're making a difference!</p>

<p>Best regards,<br>
The CharityHub Team</p>
@endsection
