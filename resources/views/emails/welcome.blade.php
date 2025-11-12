@extends('emails.layout')

@section('title', 'Welcome to CharityConnect')

@section('content')
    <h2>Welcome to CharityConnect! 🌟</h2>
    
    <p>Dear {{ $name }},</p>
    
    <div class="success-box">
        <h3 style="margin-top: 0;">Account Created Successfully! ✅</h3>
        <p>You're now part of a community dedicated to making positive change.</p>
    </div>
    
    <p>We're thrilled to have you join CharityConnect, where compassion meets action!</p>
    
    @if($role === 'donor')
        <p><strong>As a donor, you can:</strong></p>
        <ul>
            <li>Browse and support verified charity campaigns</li>
            <li>Track your donation history and impact</li>
            <li>Receive updates from the charities you support</li>
            <li>View transparent fund usage reports</li>
            <li>Engage with the charitable community</li>
        </ul>
    @elseif($role === 'charity')
        <p><strong>As a charity organization, you can:</strong></p>
        <ul>
            <li>Create fundraising campaigns</li>
            <li>Manage donations and donor relationships</li>
            <li>Post updates and engage with supporters</li>
            <li>Generate transparency reports</li>
            <li>Build trust through verified status</li>
        </ul>
    @endif
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $dashboardUrl }}" class="button">Go to Dashboard</a>
    </div>
    
    <div class="info-box">
        <p><strong>📚 Getting Started:</strong></p>
        <ul style="margin: 5px 0; padding-left: 20px;">
            <li>Complete your profile for a personalized experience</li>
            <li>Explore active campaigns in your area</li>
            <li>Connect with causes that matter to you</li>
            <li>Learn about our transparency features</li>
        </ul>
    </div>
    
    <p><strong>Need help?</strong> Our support team is here to assist you with any questions.</p>
    
    <p>Together, we're building a platform that makes giving transparent, efficient, and impactful.</p>
    
    <p>Welcome aboard!<br>
    <strong>The CharityConnect Team</strong></p>
@endsection
