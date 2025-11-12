@extends('emails.layout')

@section('title', 'Donation Confirmation - CharityConnect')

@section('content')
    <h2>Thank You for Your Generosity! 💝</h2>
    
    <p>Dear {{ $donorName }},</p>
    
    <p>We are delighted to confirm your donation. Your kindness and generosity are making a real difference!</p>
    
    <div class="success-box">
        <h3 style="margin-top: 0;">Donation Confirmed</h3>
        <p><strong>Amount:</strong> ₱{{ $amount }}</p>
        <p><strong>Campaign:</strong> {{ $campaignName }}</p>
        <p><strong>Reference Number:</strong> {{ $referenceNumber }}</p>
        <p><strong>Date:</strong> {{ $donationDate }}</p>
    </div>
    
    <p><strong>What happens next?</strong></p>
    <ol>
        <li>Your donation will be reviewed by the charity organization</li>
        <li>Once verified, it will be allocated to the campaign</li>
        <li>You'll receive updates on how your donation is being used</li>
        <li>A tax receipt will be generated if applicable</li>
    </ol>
    
    @if(isset($receiptUrl) && $receiptUrl)
    <div style="text-align: center;">
        <a href="{{ $receiptUrl }}" class="button">Download Receipt</a>
    </div>
    @endif
    
    <div class="info-box">
        <p><strong>Track Your Impact:</strong></p>
        <p>You can view detailed reports on how your donation is being utilized in your dashboard. We believe in full transparency!</p>
    </div>
    
    <p>Your contribution is helping to create positive change in our community. Thank you for being part of our mission!</p>
    
    <p>With gratitude,<br>
    <strong>The CharityConnect Team</strong></p>
@endsection
