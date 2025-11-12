@extends('emails.layout')

@section('title', 'Update from ' . $charityName)

@section('content')
    <h2>📢 New Update from {{ $charityName }}</h2>
    
    <p>Dear {{ $donorName }},</p>
    
    <div class="success-box">
        <h3 style="margin-top: 0;">Great News!</h3>
        <p>{{ $charityName }}, a charity you follow, has shared an important update.</p>
    </div>
    
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 8px; margin: 25px 0; text-align: center; color: white;">
        <h2 style="margin: 0; color: white;">{{ $updateTitle }}</h2>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">{{ $updateType }}</p>
    </div>
    
    @if($updateContent)
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <p style="margin: 0;">{{ $updateContent }}</p>
    </div>
    @endif
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $updateUrl }}" class="button" style="font-size: 18px; padding: 15px 40px;">View Full Update</a>
    </div>
    
    <div class="info-box">
        <p><strong>💡 Stay Connected:</strong></p>
        <p>You're receiving this because you follow {{ $charityName }}. Keep supporting the causes you care about!</p>
    </div>
    
    <div style="text-align: center; margin: 25px 0;">
        <a href="{{ $charityUrl }}" class="button" style="background: transparent; border: 2px solid #667eea; color: #667eea;">Visit Charity Profile</a>
    </div>
    
    <p>Thank you for being part of our community!</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Team</strong></p>
@endsection
