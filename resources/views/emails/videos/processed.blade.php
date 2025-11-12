@extends('emails.layout')

@section('content')
<h2>✅ Video Processed Successfully!</h2>

<p>Great news! Your video has been processed and is now ready to view.</p>

<div class="info-box">
    <strong>Video Details:</strong><br>
    File: {{ $videoTitle }}<br>
    Campaign: {{ $campaignTitle }}<br>
    @if(isset($duration))
    Duration: {{ $duration }}<br>
    @endif
    @if(isset($size))
    Size: {{ $size }}<br>
    @endif
</div>

<p>Your video is now live on your campaign page and can be viewed by potential donors.</p>

<p style="text-align: center;">
    <a href="{{ $campaignUrl }}" class="button">View Campaign</a>
</p>

<p>Thank you for using CharityHub!</p>
@endsection
