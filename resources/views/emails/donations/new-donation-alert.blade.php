@extends('emails.layout')

@section('title', 'New Donation Received')

@section('content')
    <h2>🎉 New Donation Received!</h2>
    
    <p>Great news!</p>
    
    <div class="success-box">
        <h3 style="margin-top: 0;">{{ $donorName }} just donated to your campaign! 💝</h3>
        <p style="font-size: 24px; font-weight: bold; color: #28a745; margin: 10px 0;">₱{{ $amount }}</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">Donation Details:</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Donor:</strong></td>
                <td style="padding: 10px 0; text-align: right;">
                    {{ $donorName }}
                    @if($isAnonymous)
                        <span style="background: #6c757d; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 5px;">
                            Anonymous
                        </span>
                    @endif
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Campaign:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $campaignTitle }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Amount:</strong></td>
                <td style="padding: 10px 0; text-align: right; color: #28a745; font-size: 18px; font-weight: bold;">₱{{ $amount }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Date & Time:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $donationDate }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Donation ID:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-family: monospace;">#{{ $donationId }}</td>
            </tr>
            @if($isRecurring)
            <tr>
                <td style="padding: 10px 0; color: #666;"><strong>Type:</strong></td>
                <td style="padding: 10px 0; text-align: right;">
                    <span style="background: #007bff; color: white; padding: 3px 10px; border-radius: 4px; font-size: 12px;">
                        RECURRING ({{ ucfirst($recurringType) }})
                    </span>
                </td>
            </tr>
            @endif
        </table>
    </div>
    
    @if($donationMessage)
    <div class="info-box">
        <p><strong>💬 Message from the Donor:</strong></p>
        <p style="font-style: italic; color: #555; padding: 10px; background: white; border-radius: 4px; border-left: 3px solid #007bff;">
            "{{ $donationMessage }}"
        </p>
    </div>
    @endif
    
    @if($isRecurring)
    <div class="success-box">
        <p><strong>🔄 Recurring Donation!</strong></p>
        <p>This is a {{ $recurringType }} recurring donation. You'll receive ongoing support from this generous donor!</p>
    </div>
    @endif
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $dashboardUrl }}" class="button">View in Dashboard</a>
    </div>
    
    <div class="info-box">
        <p><strong>📊 Next Steps:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Approve the donation in your dashboard</li>
            <li>Send a personalized thank you message to the donor</li>
            <li>Update your campaign progress and share the impact</li>
            @if(!$isAnonymous)
            <li>Consider recognizing the donor in your campaign updates</li>
            @endif
        </ul>
    </div>
    
    <p>Thank you for your dedication to making a difference!</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Team</strong></p>
@endsection
