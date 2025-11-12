@extends('emails.layout')

@section('title', 'Campaign Progress Update')

@section('content')
    @if($milestone == 100)
        <h2>🎉 Campaign Fully Funded!</h2>
    @else
        <h2>🌟 Campaign Milestone Reached!</h2>
    @endif
    
    <p>Hi {{ $donorName }},</p>
    
    @if($milestone == 100)
    <div class="success-box">
        <h3 style="margin-top: 0;">🎉 Incredible News!</h3>
        <p><strong>{{ $campaignTitle }}</strong> has reached <strong>100% of its funding goal!</strong></p>
        <p>Your donation helped make this happen! Thank you for your generosity.</p>
    </div>
    @else
    <div class="success-box">
        <h3 style="margin-top: 0;">Great Progress!</h3>
        <p><strong>{{ $campaignTitle }}</strong> has reached <strong>{{ $milestone }}%</strong> of its funding goal!</p>
        <p>Your contribution is helping to make a real difference.</p>
    </div>
    @endif
    
    @if($campaignImage)
    <div style="text-align: center; margin: 25px 0;">
        <img src="{{ $campaignImage }}" alt="{{ $campaignTitle }}" style="max-width: 100%; height: auto; border-radius: 8px; border: 1px solid #ddd;">
    </div>
    @endif
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">Campaign Progress:</h3>
        
        <div style="margin: 20px 0;">
            <div style="background: #e9ecef; height: 30px; border-radius: 15px; overflow: hidden; position: relative;">
                <div style="background: linear-gradient(90deg, #28a745 0%, #20c997 100%); height: 100%; width: {{ min($percentage, 100) }}%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">
                    {{ $percentage }}%
                </div>
            </div>
        </div>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Campaign:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $campaignTitle }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Organization:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $charityName }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Goal:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-weight: bold;">₱{{ $goalAmount }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Raised:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-weight: bold; color: #28a745;">₱{{ $currentAmount }}</td>
            </tr>
            @if($milestone != 100)
            <tr>
                <td style="padding: 10px 0; color: #666;"><strong>Remaining:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-weight: bold; color: #667eea;">₱{{ $remainingAmount }}</td>
            </tr>
            @endif
        </table>
    </div>
    
    @if($milestone == 100)
    <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); padding: 30px; border-radius: 8px; margin: 25px 0; text-align: center; color: white;">
        <p style="margin: 0; font-size: 48px;">🏆</p>
        <h3 style="margin: 10px 0; color: white;">Goal Achieved!</h3>
        <p style="margin: 0; opacity: 0.95;">This campaign has been fully funded thanks to donors like you!</p>
    </div>
    @else
    <div class="info-box">
        <p><strong>🚀 Keep the Momentum Going!</strong></p>
        <p>We're getting closer to the goal! Share this campaign with your friends and family to help us reach {{ $milestone == 50 ? '80%' : '100%' }} funding.</p>
    </div>
    @endif
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $campaignUrl }}" class="button">View Campaign</a>
        @if($milestone != 100)
        <a href="{{ $shareUrl }}" class="button" style="background: #667eea;">Share Campaign</a>
        @endif
    </div>
    
    <div class="success-box">
        <p><strong>🙏 Thank You for Your Support!</strong></p>
        <p>Your donation is making a tangible difference. The {{ $charityName }} team is working hard to bring this campaign's goals to life.</p>
    </div>
    
    <p>We're grateful for supporters like you!</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Team</strong></p>
@endsection
