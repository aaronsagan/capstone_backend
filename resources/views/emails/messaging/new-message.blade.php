@extends('emails.layout')

@section('title', 'New Message from ' . $senderName)

@section('content')
    <h2>💌 You Have a New Message</h2>
    
    <p>Dear {{ $receiverName }},</p>
    
    <div class="info-box">
        <h3 style="margin-top: 0;">📨 New Message Received</h3>
        <p><strong>{{ $senderName }}</strong> has sent you a message on CharityHub.</p>
    </div>
    
    <div style="background: white; border: 2px solid #667eea; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <div style="border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
            <p style="margin: 0; color: #666; font-size: 14px;">
                <strong>From:</strong> {{ $senderName }} • {{ $sentAt }}
            </p>
        </div>
        <p style="margin: 0; color: #333; line-height: 1.6;">{{ $messageContent }}</p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $conversationUrl }}" class="button" style="font-size: 18px; padding: 15px 40px;">Reply to Message</a>
    </div>
    
    <div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 25px 0;">
        <p style="margin: 0; color: #1565c0;"><strong>💡 Quick Reply:</strong> Click the button above to view the full conversation and send your reply.</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 25px 0;">
        <p style="margin: 0; font-size: 12px; color: #666;">
            <strong>Note:</strong> This is an automated notification. Please do not reply directly to this email. Use the CharityHub messaging system to respond.
        </p>
    </div>
    
    <p>Stay connected!</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Team</strong></p>
@endsection
