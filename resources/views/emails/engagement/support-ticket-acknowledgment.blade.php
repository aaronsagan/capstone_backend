@extends('emails.layout')

@section('title', 'Support Request Received')

@section('content')
    <h2>✅ We've Received Your Support Request</h2>
    
    <p>Hi {{ $userName }},</p>
    
    <div class="success-box">
        <h3 style="margin-top: 0;">Your ticket has been created!</h3>
        <p>Our support team has received your request and will get back to you as soon as possible.</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #333;">Ticket Details:</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Ticket ID:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-family: monospace; font-weight: bold; color: #667eea;">#{{ $ticketId }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Subject:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $ticketSubject }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px 0; color: #666;"><strong>Submitted:</strong></td>
                <td style="padding: 10px 0; text-align: right;">{{ $submittedAt }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; color: #666;"><strong>Expected Response:</strong></td>
                <td style="padding: 10px 0; text-align: right; font-weight: bold; color: #28a745;">{{ $expectedResponseTime }}</td>
            </tr>
        </table>
    </div>
    
    <div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 25px 0;">
        <p style="margin: 0 0 10px 0; color: #1565c0; font-weight: bold;">Your Message:</p>
        <p style="margin: 0; color: #424242; font-style: italic;">{{ $ticketMessage }}@if(strlen($ticketMessage) >= 300)...@endif</p>
    </div>
    
    <div class="info-box">
        <p><strong>📋 What Happens Next?</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Our support team will review your ticket</li>
            <li>We'll respond within <strong>{{ $expectedResponseTime }}</strong></li>
            <li>You'll receive an email when we reply</li>
            <li>You can track your ticket status in your dashboard</li>
        </ul>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $ticketUrl }}" class="button">View Ticket Status</a>
        <a href="{{ $supportUrl }}" class="button" style="background: #6c757d;">Browse Support Center</a>
    </div>
    
    <div class="warning-box">
        <p><strong>💡 Helpful Tips:</strong></p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Keep this ticket ID for reference: <strong>#{{ $ticketId }}</strong></li>
            <li>Check your email for updates on your ticket</li>
            <li>You can reply to this ticket from your dashboard</li>
            <li>For urgent matters, please use our live chat support</li>
        </ul>
    </div>
    
    <div class="info-box">
        <p><strong>📞 Need Immediate Help?</strong></p>
        <p>For urgent issues, you can also reach us through:</p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong>Email:</strong> support@charityhub.com</li>
            <li><strong>Hours:</strong> Monday-Friday, 9 AM - 6 PM PHT</li>
        </ul>
    </div>
    
    <p>Thank you for contacting CharityHub Support!</p>
    
    <p>Best regards,<br>
    <strong>The CharityHub Support Team</strong></p>
@endsection
