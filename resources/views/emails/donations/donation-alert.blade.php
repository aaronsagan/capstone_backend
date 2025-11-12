@extends('emails.layout')

@section('title', 'New Donation Received')

@section('content')
    <h2>🎉 New Donation Received!</h2>
    
    <p>Dear {{ $charityName }},</p>
    
    <div class="success-box">
        <h3 style="margin-top: 0;">You've received a new donation!</h3>
        <p>A generous supporter has just contributed to your cause.</p>
    </div>
    
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 8px; margin: 25px 0; text-align: center; color: white;">
        <p style="margin: 0; font-size: 16px; opacity: 0.9;">Donation Amount</p>
        <h1 style="margin: 10px 0; color: white; font-size: 48px;">₱{{ number_format($amount, 2) }}</h1>
        <p style="margin: 0; opacity: 0.9;">{{ $campaignTitle }}</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">Donation Details:</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Donor:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $donorName }}</td>
            </tr>
            @if($campaignTitle)
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Campaign:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $campaignTitle }}</td>
            </tr>
            @endif
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Amount:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-weight: bold; color: #28a745;">₱{{ number_format($amount, 2) }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Transaction ID:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-family: monospace;">{{ $transactionId }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; color: #666;"><strong>Date:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $donatedAt }}</td>
            </tr>
        </table>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $dashboardUrl }}" class="button">View in Dashboard</a>
    </div>
    
    <div class="info-box">
        <p><strong>💡 Next Steps:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong>Acknowledge the donation:</strong> Send a thank you message to your donor</li>
            <li><strong>Update your campaign:</strong> Post updates on how funds are being used</li>
            <li><strong>Build relationships:</strong> Engage with your supporters regularly</li>
            <li><strong>Track impact:</strong> Document the difference this donation is making</li>
        </ul>
    </div>
    
    <div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 25px 0;">
        <p style="margin: 0; color: #1565c0;"><strong>📊 Pro Tip:</strong> Donors who feel appreciated are more likely to give again. Consider sending a personalized thank you message and sharing updates about your progress.</p>
    </div>
    
    <p>Keep up the great work making a difference!</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Team</strong></p>
@endsection
