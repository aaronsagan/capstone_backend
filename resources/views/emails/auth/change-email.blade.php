@extends('emails.layout')

@section('title', 'Confirm Email Change')

@section('content')
    <h2>Confirm Your New Email Address</h2>
    
    <p>Hello {{ $userName }},</p>
    
    <p>We received a request to change the email address associated with your CharityConnect account.</p>
    
    <div class="info-box">
        <p><strong>Email Change Details:</strong></p>
        <ul style="margin: 5px 0; padding-left: 20px;">
            <li><strong>Current Email:</strong> {{ $oldEmail }}</li>
            <li><strong>New Email:</strong> {{ $newEmail }}</li>
        </ul>
    </div>
    
    <p>To confirm this change and update your email address, please click the button below:</p>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $confirmUrl }}" class="button">Confirm Email Change</a>
    </div>
    
    <p>Or copy and paste this link into your browser:</p>
    <div class="info-box">
        <code style="word-break: break-all; font-size: 12px;">{{ $confirmUrl }}</code>
    </div>
    
    <div class="warning-box">
        <strong>⚠️ Security Notice:</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>This confirmation link will expire in <strong>{{ $expiresIn }} minutes</strong></li>
            <li>If you didn't request this change, please <strong>ignore this email</strong> and contact support immediately</li>
            <li>Your current email will remain active until you confirm the change</li>
            <li>After confirmation, all future communications will be sent to your new email</li>
        </ul>
    </div>
    
    <div class="info-box">
        <p><strong>What happens after confirmation?</strong></p>
        <ul style="margin: 5px 0; padding-left: 20px;">
            <li>Your login email will be updated to {{ $newEmail }}</li>
            <li>All notifications will be sent to the new email</li>
            <li>You'll need to use the new email for future logins</li>
        </ul>
    </div>
    
    <p>If you didn't initiate this change, your account security may be compromised. Please contact support immediately.</p>
    
    <p>Best regards,<br>
    <strong>The CharityConnect Security Team</strong></p>
@endsection
