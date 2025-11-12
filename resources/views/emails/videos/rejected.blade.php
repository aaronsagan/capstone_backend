@extends('emails.layout')

@section('content')
<h2>❌ Video Upload Rejected</h2>

<p>We're sorry, but your video upload could not be processed.</p>

<div class="warning-box">
    <strong>Video Details:</strong><br>
    File: {{ $videoTitle }}<br>
    Campaign: {{ $campaignTitle }}<br><br>
    <strong>Reason:</strong><br>
    {{ $reason }}
</div>

<p><strong>What to do next:</strong></p>
<ul>
    <li>Check if your video meets the requirements (MP4/WEBM/MOV, max 50MB, max 5 minutes)</li>
    <li>Ensure the video file is not corrupted</li>
    <li>Try uploading a different version of your video</li>
</ul>

<p style="text-align: center;">
    <a href="{{ $uploadUrl }}" class="button">Try Again</a>
</p>

<p>If you continue to experience issues, please contact our support team.</p>
@endsection
