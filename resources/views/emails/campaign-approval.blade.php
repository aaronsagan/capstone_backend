@extends('emails.layout')

@section('title', 'Campaign Status Update - CharityConnect')

@section('content')
    @if($status === 'approved')
        <h2>🎊 Campaign Approved!</h2>
        
        <p>Hello {{ $charityName }},</p>
        
        <div class="success-box">
            <h3 style="margin-top: 0;">Great News! ✅</h3>
            <p>Your campaign <strong>"{{ $campaignName }}"</strong> has been approved and is now live!</p>
        </div>
        
        <p><strong>What this means:</strong></p>
        <ul>
            <li>Your campaign is now visible to all donors</li>
            <li>You can start receiving donations</li>
            <li>Campaign updates can be posted</li>
            <li>Full access to campaign management tools</li>
        </ul>
        
        <div style="text-align: center;">
            <a href="{{ $campaignUrl }}" class="button">View Campaign</a>
        </div>
        
        <div class="info-box">
            <p><strong>💡 Pro Tips:</strong></p>
            <ul style="margin: 5px 0; padding-left: 20px;">
                <li>Post regular updates to engage donors</li>
                <li>Share your campaign on social media</li>
                <li>Keep donors informed about fund usage</li>
                <li>Thank donors for their contributions</li>
            </ul>
        </div>
        
    @else
        <h2>Campaign Status Update</h2>
        
        <p>Hello {{ $charityName }},</p>
        
        <div class="warning-box">
            <h3 style="margin-top: 0;">Campaign Status: {{ ucfirst($status) }}</h3>
            <p>Your campaign <strong>"{{ $campaignName }}"</strong> status has been updated.</p>
        </div>
        
        <p>Please review the details in your dashboard and take any necessary actions.</p>
        
        <div style="text-align: center;">
            <a href="{{ $campaignUrl }}" class="button">View Campaign Details</a>
        </div>
    @endif
    
    <p>Thank you for using CharityConnect to make a difference!</p>
    
    <p>Best regards,<br>
    <strong>The CharityConnect Team</strong></p>
@endsection
