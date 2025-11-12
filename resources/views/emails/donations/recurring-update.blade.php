@extends('emails.layout')

@section('title', 'Recurring Donation Update')

@section('content')
    @if($action === 'paused')
        <h2>⏸️ Recurring Donation Paused</h2>
    @elseif($action === 'resumed')
        <h2>▶️ Recurring Donation Resumed</h2>
    @elseif($action === 'cancelled')
        <h2>🛑 Recurring Donation Cancelled</h2>
    @endif
    
    <p>Dear {{ $donorName }},</p>
    
    @if($action === 'paused')
        <div class="warning-box">
            <h3 style="margin-top: 0;">Your recurring donation has been paused</h3>
            <p>Your {{ $recurringType }} donation to <strong>{{ $charityName }}</strong> is now on hold.</p>
        </div>
        
        <p>No further charges will be processed until you resume your recurring donation.</p>
        
    @elseif($action === 'resumed')
        <div class="success-box">
            <h3 style="margin-top: 0;">Welcome back! Your recurring donation has been resumed 🎉</h3>
            <p>Thank you for continuing your support for <strong>{{ $charityName }}</strong>!</p>
        </div>
        
        <p>Your {{ $recurringType }} donations will now continue as scheduled.</p>
        
    @elseif($action === 'cancelled')
        <div class="warning-box">
            <h3 style="margin-top: 0;">Your recurring donation has been cancelled</h3>
            <p>We're sorry to see you go. Your recurring donation to <strong>{{ $charityName }}</strong> has been stopped.</p>
        </div>
        
        <p>No further charges will be processed. Your previous donations will remain on record.</p>
    @endif
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">Donation Details:</h3>
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
                <td style="padding: 10px 0; color: #666;"><strong>Amount:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-weight: bold;">₱{{ $amount }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Frequency:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ ucfirst($recurringType) }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Status:</strong></td>
                <td style="padding: 10px 0; text-align: right;">
                    @if($action === 'paused')
                        <span style="background: #ffc107; color: #000; padding: 3px 10px; border-radius: 4px; font-size: 12px;">
                            PAUSED
                        </span>
                    @elseif($action === 'resumed')
                        <span style="background: #28a745; color: white; padding: 3px 10px; border-radius: 4px; font-size: 12px;">
                            ACTIVE
                        </span>
                    @else
                        <span style="background: #dc3545; color: white; padding: 3px 10px; border-radius: 4px; font-size: 12px;">
                            CANCELLED
                        </span>
                    @endif
                </td>
            </tr>
            @if($nextBillingDate)
            <tr>
                <td style="padding: 10px 0; color: #666;"><strong>Next Billing Date:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-weight: bold; color: #007bff;">{{ $nextBillingDate }}</td>
            </tr>
            @endif
        </table>
    </div>
    
    @if($action === 'resumed' && $nextBillingDate)
    <div class="info-box">
        <p><strong>📅 Next Donation:</strong></p>
        <p>Your next {{ $recurringType }} donation of <strong>₱{{ $amount }}</strong> will be processed on <strong>{{ $nextBillingDate }}</strong>.</p>
    </div>
    @endif
    
    @if($action === 'paused')
    <div class="info-box">
        <p><strong>💡 Ready to Resume?</strong></p>
        <p>You can resume your recurring donation anytime from your dashboard. Your continued support makes a real difference!</p>
    </div>
    @elseif($action === 'cancelled')
    <div class="info-box">
        <p><strong>💙 Thank You for Your Support!</strong></p>
        <p>While your recurring donation has ended, we're incredibly grateful for your past contributions. You're always welcome to make one-time donations or restart recurring support anytime.</p>
    </div>
    @endif
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $manageUrl }}" class="button">Manage Recurring Donations</a>
    </div>
    
    <p>If you have any questions or need assistance, please don't hesitate to reach out to our support team.</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Team</strong></p>
@endsection
