@extends('emails.layout')

@section('title', 'Campaign Ending Soon')

@section('content')
    <h2>⏰ Campaign Ending Soon!</h2>
    
    <p>Dear {{ $donorName }},</p>
    
    <div class="warning-box">
        <h3 style="margin-top: 0;">⚠️ Time is Running Out!</h3>
        <p>A campaign you saved is ending in <strong>{{ $daysLeft }} days</strong>. Don't miss your chance to make a difference!</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">{{ $campaignTitle }}</h3>
        <p style="color: #666; margin: 5px 0;"><strong>Charity:</strong> {{ $charityName }}</p>
        
        <div style="margin: 15px 0;">
            <div style="background: #e0e0e0; height: 20px; border-radius: 10px; overflow: hidden;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100%; width: {{ $progressPercentage }}%; transition: width 0.3s;"></div>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 5px;">
                <span style="font-size: 12px; color: #666;">₱{{ number_format($currentAmount, 2) }} raised</span>
                <span style="font-size: 12px; color: #666;">{{ $progressPercentage }}% of ₱{{ number_format($goalAmount, 2) }}</span>
            </div>
        </div>
        
        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin-top: 15px;">
            <p style="margin: 0; color: #856404;"><strong>⏰ Ends on:</strong> {{ $endDate }}</p>
        </div>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $campaignUrl }}" class="button" style="font-size: 18px; padding: 15px 40px;">Donate Now</a>
    </div>
    
    <div class="info-box">
        <p><strong>Why your support matters:</strong></p>
        <p>Every donation brings this campaign closer to its goal. Your contribution, no matter the size, makes a real impact on the lives this charity serves.</p>
    </div>
    
    <p>Don't let this opportunity pass!</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Team</strong></p>
@endsection
