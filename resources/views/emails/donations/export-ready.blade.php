@extends('emails.layout')

@section('title', 'Donation Export Ready')

@section('content')
    <h2>📥 Your Donation Export is Ready!</h2>
    
    <p>Dear {{ $userName }},</p>
    
    <div class="success-box">
        <h3 style="margin-top: 0;">Your donation history has been exported</h3>
        <p>The {{ $exportType }} file you requested is attached to this email.</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">Export Details:</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Format:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $exportType }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Records:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $recordCount }} donations</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; color: #666;"><strong>Generated:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $generatedDate }}</td>
            </tr>
        </table>
    </div>
    
    <div class="info-box">
        <p><strong>📎 Attached File:</strong></p>
        <p>Your complete donation history is attached. You can use this for record-keeping or tax purposes.</p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $dashboardUrl }}" class="button">Back to Dashboard</a>
    </div>
    
    <p>Thank you for using CharityHub!</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Team</strong></p>
@endsection
