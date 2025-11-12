@extends('emails.layout')

@section('title', 'Campaign Ending Soon')

@section('content')
    <h2>⏰ Reminder: Campaign Ending Soon!</h2>
    
    <p>Hi {{ $donorName }},</p>
    
    <div class="warning-box">
        <h3 style="margin-top: 0;">Time is running out!</h3>
        <p>A campaign you saved is ending in <strong>{{ $daysRemaining }} {{ $daysRemaining == 1 ? 'day' : 'days' }}</strong>.</p>
    </div>
    
    @if($campaignImage)
    <div style="text-align: center; margin: 25px 0;">
        <img src="{{ $campaignImage }}" alt="{{ $campaignTitle }}" style="max-width: 100%; height: auto; border-radius: 8px; border: 1px solid #ddd;">
    </div>
    @endif
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">{{ $campaignTitle }}</h3>
        <p style="color: #666; margin: 10px 0;">{{ $campaignDescription }}</p>
        
        <div style="margin: 20px 0;">
            <div style="background: #e9ecef; height: 20px; border-radius: 10px; overflow: hidden;">
                <div style="background: linear-gradient(90deg, #28a745 0%, #20c997 100%); height: 100%; width: {{ min($percentageFunded, 100) }}%;"></div>
            </div>
            <p style="margin: 10px 0 0 0; font-size: 14px; color: #666;">
                <strong>{{ $percentageFunded }}%</strong> funded · 
                <strong>₱{{ $currentAmount }}</strong> of <strong>₱{{ $goalAmount }}</strong>
            </p>
        </div>
    </div>
    
    <div style="background: #dc3545; color: white; padding: 20px; border-radius: 8px; margin: 25px 0; text-align: center;">
        <p style="margin: 0 0 5px 0; font-size: 24px; font-weight: bold;">{{ $daysRemaining }}</p>
        <p style="margin: 0; opacity: 0.9;">{{ $daysRemaining == 1 ? 'Day' : 'Days' }} Remaining</p>
        <p style="margin: 10px 0 0 0; font-size: 14px; opacity: 0.9;">Campaign ends on {{ $endDate }}</p>
    </div>
    
    <div class="success-box">
        <p><strong>🎯 Your Impact Matters:</strong></p>
        <p>There's still time to support this important cause. Your donation, no matter the size, can make a real difference before the campaign closes.</p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $donateUrl }}" class="button" style="font-size: 18px; padding: 15px 40px;">Donate Now</a>
        <br><br>
        <a href="{{ $campaignUrl }}" style="color: #667eea; text-decoration: underline;">View Campaign Details</a>
    </div>
    
    <div class="info-box">
        <p><strong>⏰ Don't miss this opportunity!</strong></p>
        <p>Once the campaign ends, you won't be able to donate anymore. Act now to support this cause!</p>
    </div>
    
    <p>Thank you for considering supporting this campaign!</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Team</strong></p>
@endsection
