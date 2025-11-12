@extends('emails.layout')

@section('title', 'Annual Donation Statement')

@section('content')
    <h2>📊 Your {{ $year }} Annual Donation Statement</h2>
    
    <p>Dear {{ $userName }},</p>
    
    <div class="success-box">
        <h3 style="margin-top: 0;">Thank you for your generosity in {{ $year }}! 🎉</h3>
        <p>Your donations have made a real difference. Here's your charitable giving summary.</p>
    </div>
    
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 8px; margin: 25px 0; text-align: center; color: white;">
        <h3 style="margin: 0 0 20px 0; color: white;">{{ $year }} Giving Summary</h3>
        <div style="font-size: 36px; font-weight: bold; margin: 10px 0;">₱{{ $totalAmount }}</div>
        <div style="font-size: 14px; opacity: 0.9; margin: 5px 0;">Total Donated</div>
        <div style="font-size: 24px; font-weight: bold; margin: 10px 0;">{{ $donationCount }} Donations</div>
        <div style="font-size: 24px; font-weight: bold; margin: 10px 0;">{{ $campaignsSupported }} Campaigns</div>
    </div>
    
    <div class="info-box">
        <p><strong>📎 Attached Document:</strong></p>
        <p>Your detailed statement is attached as a PDF.</p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $dashboardUrl }}" class="button">View Full History</a>
    </div>
    
    <p>Thank you for being a valued supporter!</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Team</strong></p>
@endsection
