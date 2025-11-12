@extends('emails.layout')

@section('title', 'Donation Confirmation')

@section('content')
    <h2>🎉 Thank You for Your Generous Donation!</h2>
    
    <p>Dear {{ $donorName }},</p>
    
    <div class="success-box">
        <h3 style="margin-top: 0;">Your donation has been received! 💝</h3>
        <p>Thank you for supporting <strong>{{ $charityName }}</strong> and making a difference in the lives of those in need.</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">Donation Details:</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Campaign:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $campaignTitle }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Amount:</strong></td>
                <td style="padding: 10px 0; text-align: right; color: #28a745; font-size: 18px; font-weight: bold;">₱{{ $amount }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Transaction ID:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-family: monospace;">{{ $transactionId }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Date & Time:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $donationDate }}</td>
            </tr>
            @if($receiptNumber !== 'PENDING')
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Receipt Number:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-family: monospace;">{{ $receiptNumber }}</td>
            </tr>
            @endif
            @if($isRecurring)
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Type:</strong></td>
                <td style="padding: 10px 0; text-align: right;">
                    <span style="background: #007bff; color: white; padding: 3px 10px; border-radius: 4px; font-size: 12px;">
                        RECURRING ({{ ucfirst($recurringType) }})
                    </span>
                </td>
            </tr>
            @endif
            @if($isAnonymous)
            <tr>
                <td style="padding: 10px 0; color: #666;"><strong>Privacy:</strong></td>
                <td style="padding: 10px 0; text-align: right;">
                    <span style="background: #6c757d; color: white; padding: 3px 10px; border-radius: 4px; font-size: 12px;">
                        ANONYMOUS
                    </span>
                </td>
            </tr>
            @endif
        </table>
    </div>
    
    @if($isRecurring)
    <div class="info-box">
        <p><strong>📅 About Your Recurring Donation:</strong></p>
        <p>Your donation will automatically recur <strong>{{ $recurringType }}</strong>. You can manage or cancel your recurring donation at any time from your dashboard.</p>
    </div>
    @endif
    
    <div class="success-box">
        <p><strong>🌟 Your Impact:</strong></p>
        <p>Your generosity directly supports {{ $charityName }}'s mission. Every contribution makes a meaningful difference in creating positive change.</p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $dashboardUrl }}" class="button">View Donation History</a>
    </div>
    
    <div class="info-box">
        <p><strong>📧 Need a Receipt?</strong></p>
        <p>Your official donation receipt will be available in your dashboard. You can download it anytime for your tax records.</p>
    </div>
    
    <p>Thank you for being a valued supporter of CharityHub. Together, we're making the world a better place!</p>
    
    <p>With gratitude,<br>
    <strong>The CharityHub Team</strong></p>
@endsection
