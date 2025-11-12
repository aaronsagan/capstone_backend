@extends('emails.layout')

@section('title', 'New Message Received')

@section('content')
    <h2>💬 You have a new message!</h2>
    
    <p>Hi {{ $recipientName }},</p>
    
    <div class="info-box">
        <p><strong>{{ $senderName }}</strong> sent you a message on CharityHub.</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>From:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $senderName }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Email:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-family: monospace;">{{ $senderEmail }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; color: #666;"><strong>Time:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $timestamp }}</td>
            </tr>
        </table>
    </div>
    
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 8px; margin: 25px 0; color: white;">
        <p style="margin: 0 0 10px 0; opacity: 0.9; font-size: 14px;">Message Preview:</p>
        <p style="margin: 0; font-style: italic; font-size: 16px;">{{ $messagePreview }}@if(strlen($messagePreview) >= 100)...@endif</p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $messagesUrl }}" class="button" style="font-size: 18px; padding: 15px 40px;">View Message</a>
        <br><br>
        <a href="{{ $replyUrl }}" style="color: #667eea; text-decoration: underline;">Reply to {{ $senderName }}</a>
    </div>
    
    <div class="info-box">
        <p><strong>💡 Quick Tips:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Click "View Message" to see the full conversation</li>
            <li>You can reply directly from your inbox</li>
            <li>Message history is saved in your account</li>
            <li>You'll be notified of new replies via email</li>
        </ul>
    </div>
    
    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 25px 0;">
        <p style="margin: 0; color: #856404;"><strong>📧 Notification Settings:</strong> You can manage your message notification preferences in your <a href="{{ config('app.frontend_url') }}/donor/settings/notifications" style="color: #856404; text-decoration: underline;">settings</a>.</p>
    </div>
    
    <p>Stay connected with the CharityHub community!</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Team</strong></p>
@endsection
