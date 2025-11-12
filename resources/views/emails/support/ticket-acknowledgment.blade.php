@extends('emails.layout')

@section('title', 'Support Ticket Received')

@section('content')
    <h2>🎫 Support Ticket Received</h2>
    
    <p>Dear {{ $userName }},</p>
    
    <div class="success-box">
        <h3 style="margin-top: 0;">✅ We've Got Your Request!</h3>
        <p>Your support ticket has been successfully created. Our team will review it shortly.</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">Ticket Details</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Ticket ID:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-family: monospace;">#{{ $ticketId }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Subject:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $subject }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Priority:</strong></td>
                <td style="padding: 10px 0; text-align: right;">
                    <span style="padding: 3px 10px; border-radius: 4px; font-size: 12px; background: {{ $priority === 'urgent' ? '#dc3545' : ($priority === 'high' ? '#ff9800' : '#28a745') }}; color: white;">
                        {{ ucfirst($priority) }}
                    </span>
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Status:</strong></td>
                <td style="padding: 10px 0; text-align: right;">
                    <span style="background: #28a745; color: white; padding: 3px 10px; border-radius: 4px; font-size: 12px;">Open</span>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px 0; color: #666;"><strong>Created:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $createdAt }}</td>
            </tr>
        </table>
    </div>
    
    @if($initialMessage)
    <div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 25px 0;">
        <p style="margin: 0 0 5px 0; color: #1565c0; font-weight: bold;">Your Message:</p>
        <p style="margin: 0; color: #424242;">{{ $initialMessage }}</p>
    </div>
    @endif
    
    <div class="info-box">
        <p><strong>⏱️ What Happens Next:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong>Review:</strong> Our support team will review your ticket within 24 hours</li>
            <li><strong>Assignment:</strong> Your ticket will be assigned to a specialist</li>
            <li><strong>Response:</strong> You'll receive an email when we reply</li>
            <li><strong>Updates:</strong> Check your ticket status anytime in the support portal</li>
        </ul>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $ticketUrl }}" class="button">View Ticket</a>
    </div>
    
    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 25px 0;">
        <p style="margin: 0; color: #856404;"><strong>💡 Tip:</strong> Keep ticket #{{ $ticketId }} for your reference. You can reply directly to this email or through the support portal.</p>
    </div>
    
    <p>We're here to help!</p>
    
    <p>Best regards,<br>
    <strong>CharityHub Support Team</strong></p>
@endsection
