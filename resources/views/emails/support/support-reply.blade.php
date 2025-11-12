@extends('emails.layout')

@section('title', 'New Reply to Your Support Ticket')

@section('content')
    <h2>💬 New Reply from Support</h2>
    
    <p>Dear {{ $userName }},</p>
    
    <div class="info-box">
        <h3 style="margin-top: 0;">📬 You Have a New Message</h3>
        <p>Our support team has replied to your ticket <strong>#{{ $ticketId }}</strong>.</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">Ticket: {{ $subject }}</h3>
        <p style="color: #666; margin: 5px 0;"><strong>Status:</strong> 
            <span style="background: {{ $status === 'resolved' ? '#28a745' : '#2196f3' }}; color: white; padding: 3px 10px; border-radius: 4px; font-size: 12px;">
                {{ ucfirst($status) }}
            </span>
        </p>
    </div>
    
    <div style="background: white; border: 2px solid #667eea; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <div style="border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
            <p style="margin: 0; color: #666; font-size: 14px;">
                <strong>{{ $staffName }}</strong> • {{ $repliedAt }}
            </p>
        </div>
        <p style="margin: 0; color: #333; line-height: 1.6;">{{ $replyMessage }}</p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $ticketUrl }}" class="button">View Full Conversation</a>
    </div>
    
    @if($status !== 'resolved')
    <div class="info-box">
        <p><strong>Need to Reply?</strong></p>
        <p>You can respond directly through the support portal or by replying to this email. Your response will be added to ticket #{{ $ticketId }}.</p>
    </div>
    @else
    <div class="success-box">
        <p><strong>✅ Ticket Resolved</strong></p>
        <p>This ticket has been marked as resolved. If you still need help, you can reopen it by replying.</p>
    </div>
    @endif
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0; text-align: center;">
        <p style="margin: 0 0 10px 0; color: #666;">📞 <strong>Need Immediate Help?</strong></p>
        <p style="margin: 0; color: #666;">
            <strong>Email:</strong> support@charityhub.com<br>
            <strong>Hours:</strong> Monday-Friday, 9 AM - 6 PM PHT
        </p>
    </div>
    
    <p>Thank you for using CharityHub!</p>
    
    <p>Best regards,<br>
    <strong>CharityHub Support Team</strong></p>
@endsection
