@extends('emails.layout')

@section('title', 'Payment Method Update')

@section('content')
    @if($action === 'added')
        <h2>✅ Payment Method Added Successfully!</h2>
    @elseif($action === 'removed')
        <h2>🗑️ Payment Method Removed</h2>
    @elseif($action === 'changed')
        <h2>🔄 Payment Method Updated</h2>
    @endif
    
    <p>Dear {{ $userName }},</p>
    
    @if($action === 'added')
        <div class="success-box">
            <h3 style="margin-top: 0;">New payment method added to your account</h3>
            <p>You have successfully added a new payment method to your CharityHub account.</p>
        </div>
    @elseif($action === 'removed')
        <div class="info-box">
            <h3 style="margin-top: 0;">Payment method removed from your account</h3>
            <p>A payment method has been removed from your CharityHub account.</p>
        </div>
    @elseif($action === 'changed')
        <div class="info-box">
            <h3 style="margin-top: 0;">Payment method updated successfully</h3>
            <p>Your payment method information has been updated in your CharityHub account.</p>
        </div>
    @endif
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">Update Details:</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Action:</strong></td>
                <td style="padding: 10px 0; text-align: right;">
                    @if($action === 'added')
                        <span style="background: #28a745; color: white; padding: 3px 10px; border-radius: 4px; font-size: 12px;">
                            ADDED
                        </span>
                    @elseif($action === 'removed')
                        <span style="background: #dc3545; color: white; padding: 3px 10px; border-radius: 4px; font-size: 12px;">
                            REMOVED
                        </span>
                    @else
                        <span style="background: #007bff; color: white; padding: 3px 10px; border-radius: 4px; font-size: 12px;">
                            UPDATED
                        </span>
                    @endif
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Payment Type:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-weight: bold;">{{ $paymentType }}</td>
            </tr>
            @if($last4Digits)
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Account/Card:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-family: monospace;">**** **** **** {{ $last4Digits }}</td>
            </tr>
            @endif
            <tr>
                <td style="padding: 10px 0; color: #666;"><strong>Date & Time:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $timestamp }}</td>
            </tr>
        </table>
    </div>
    
    @if($action === 'added')
    <div class="success-box">
        <p><strong>🎉 What's Next?</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>You can now use this payment method for donations</li>
            <li>Set it as your default payment method if desired</li>
            <li>Update your recurring donation payment methods</li>
        </ul>
    </div>
    @elseif($action === 'removed')
    <div class="warning-box">
        <p><strong>⚠️ Important Notice:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>This payment method is no longer available for donations</li>
            <li>Recurring donations using this method may be affected</li>
            <li>Please ensure you have an alternative payment method on file</li>
        </ul>
    </div>
    @endif
    
    <div class="info-box">
        <p><strong>🔒 Security Notice:</strong></p>
        <p>If you did not make this change, please contact our support team immediately and review your account security settings.</p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $dashboardUrl }}" class="button">Manage Payment Methods</a>
    </div>
    
    <p>Thank you for using CharityHub to make a difference!</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Team</strong></p>
@endsection
