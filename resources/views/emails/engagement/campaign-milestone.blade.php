@extends('emails.layout')

@section('title', 'Campaign Milestone Reached!')

@section('content')
    <h2>🎉 Milestone Achieved!</h2>
    
    <p>Dear {{ $donorName }},</p>
    
    <div class="success-box">
        <h3 style="margin-top: 0;">🎊 Amazing Progress!</h3>
        <p>Great news! A campaign you supported has reached an important milestone.</p>
    </div>
    
    <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); padding: 30px; border-radius: 8px; margin: 25px 0; text-align: center; color: white;">
        <p style="margin: 0; font-size: 64px;">{{ $milestonePercentage }}%</p>
        <h2 style="margin: 10px 0; color: white;">{{ $campaignTitle }}</h2>
        <p style="margin: 0; opacity: 0.95;">has reached {{ $milestonePercentage }}% of its goal!</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">Campaign Progress</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Amount Raised:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-weight: bold; color: #28a745;">₱{{ number_format($currentAmount, 2) }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Goal:</strong></td>
                <td style="padding: 10px 0; text-align: right;">₱{{ number_format($goalAmount, 2) }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Donors:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $donorCount }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; color: #666;"><strong>Charity:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $charityName }}</td>
            </tr>
        </table>
    </div>
    
    <div class="info-box">
        <p><strong>🤝 Your Impact:</strong></p>
        <p>Your donation helped make this milestone possible! Together with {{ $donorCount }} other supporters, you're making a real difference in the community.</p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $campaignUrl }}" class="button">View Campaign Progress</a>
    </div>
    
    <div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 25px 0;">
        <p style="margin: 0; color: #1565c0;"><strong>💪 Help Us Reach 100%!</strong></p>
        <p style="margin: 5px 0 0 0; color: #424242;">Share this campaign with your friends and family to help us reach our goal faster!</p>
    </div>
    
    <div style="text-align: center; margin: 25px 0;">
        <a href="{{ $shareUrl }}" class="button" style="background: #1DA1F2;">Share on Social Media</a>
    </div>
    
    <p>Thank you for being an amazing supporter!</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Team</strong></p>
@endsection
