@extends('emails.layout')

@section('title', 'New Update from Charity')

@section('content')
    <h2>🎉 New Campaign from {{ $charityName }}!</h2>
    
    <p>Hi {{ $donorName }},</p>
    
    <div class="success-box">
        <p><strong>Good news!</strong> {{ $charityName }}, a charity you follow, has just launched a new campaign!</p>
    </div>
    
    @if($campaignImage)
    <div style="text-align: center; margin: 25px 0;">
        <img src="{{ $campaignImage }}" alt="{{ $campaignTitle }}" style="max-width: 100%; height: auto; border-radius: 8px; border: 1px solid #ddd;">
    </div>
    @endif
    
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 8px; margin: 25px 0; color: white;">
        <h3 style="margin: 0 0 10px 0; color: white;">{{ $campaignTitle }}</h3>
        <p style="margin: 0; opacity: 0.95;">{{ $campaignDescription }}</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">Campaign Details:</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Organization:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $charityName }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; color: #666;"><strong>Goal Amount:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-size: 18px; font-weight: bold; color: #28a745;">₱{{ $goalAmount }}</td>
            </tr>
        </table>
    </div>
    
    <div class="info-box">
        <p><strong>💝 Support This Campaign:</strong></p>
        <p>Your donation can make a real difference. Every contribution helps bring this campaign closer to its goal!</p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $campaignUrl }}" class="button">View Campaign</a>
        <a href="{{ $charityUrl }}" class="button" style="background: #6c757d;">View Charity Profile</a>
    </div>
    
    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 25px 0;">
        <p style="margin: 0; color: #856404;"><strong>📧 Notification Settings:</strong> You're receiving this because you follow {{ $charityName }}. You can manage your notification preferences in your <a href="{{ $unsubscribeUrl }}" style="color: #856404; text-decoration: underline;">settings</a>.</p>
    </div>
    
    <p>Thank you for being part of the CharityHub community!</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Team</strong></p>
@endsection
