@extends('emails.layout')

@section('title', 'Charity Account Approved')

@section('content')
    <h2>🎉 Congratulations!</h2>
    
    <p>Dear {{ $userName }},</p>
    
    <div class="success-box">
        <h3 style="margin-top: 0;">Your charity account has been approved!</h3>
        <p>We're excited to welcome <strong>{{ $charityName }}</strong> to the CharityHub community. Your organization can now start creating campaigns and receiving donations.</p>
    </div>
    
    <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); padding: 30px; border-radius: 8px; margin: 25px 0; text-align: center; color: white;">
        <p style="margin: 0; font-size: 48px;">✅</p>
        <h3 style="margin: 10px 0; color: white;">Account Approved</h3>
        <p style="margin: 0; opacity: 0.95;">Approved on {{ $approvedAt }}</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">🚀 What You Can Do Now:</h3>
        <div style="margin: 15px 0;">
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 10px 0;">
                        <strong style="color: #28a745;">✓</strong> <strong>Create Donation Campaigns</strong>
                        <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Launch fundraising campaigns for your causes</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; border-top: 1px solid #ddd;">
                        <strong style="color: #28a745;">✓</strong> <strong>Manage Donor Contributions</strong>
                        <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Track and acknowledge donations in real-time</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; border-top: 1px solid #ddd;">
                        <strong style="color: #28a745;">✓</strong> <strong>Track Your Progress</strong>
                        <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Monitor campaign performance and donor engagement</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; border-top: 1px solid #ddd;">
                        <strong style="color: #28a745;">✓</strong> <strong>Post Updates & Milestones</strong>
                        <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Keep your supporters informed of your impact</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; border-top: 1px solid #ddd;">
                        <strong style="color: #28a745;">✓</strong> <strong>Access Transparency Tools</strong>
                        <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Build trust with detailed reporting features</p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $dashboardUrl }}" class="button" style="font-size: 18px; padding: 15px 40px;">Go to Dashboard</a>
        <br><br>
        <a href="{{ $createCampaignUrl }}" class="button" style="background: #28a745;">Create Your First Campaign</a>
    </div>
    
    <div class="info-box">
        <p><strong>💡 Getting Started Tips:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong>Complete your profile:</strong> Add a logo, description, and contact information</li>
            <li><strong>Create your first campaign:</strong> Share your story and set a funding goal</li>
            <li><strong>Engage with donors:</strong> Thank supporters and provide regular updates</li>
            <li><strong>Build transparency:</strong> Share how donations are being used</li>
        </ul>
    </div>
    
    <div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 25px 0;">
        <p style="margin: 0; color: #1565c0;"><strong>📚 Resources Available:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px; color: #424242;">
            <li>Campaign creation guide</li>
            <li>Best practices for fundraising</li>
            <li>Donor engagement strategies</li>
            <li>Financial reporting tools</li>
        </ul>
    </div>
    
    <div class="success-box">
        <p><strong>🤝 Need Help?</strong></p>
        <p>Our support team is here to assist you! If you have any questions or need guidance getting started, don't hesitate to reach out.</p>
    </div>
    
    <p>Welcome to CharityHub! We're honored to support your mission and can't wait to see the positive impact you'll make.</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Team</strong></p>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 25px 0; font-size: 12px; color: #666;">
        <p style="margin: 0;"><em>If you did not apply for a charity account, please contact us immediately at support@charityhub.com</em></p>
    </div>
@endsection
