<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Request {{ ucfirst($status) }} - CharityHub</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: {{ $status === 'approved' ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)' : 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)' }};
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .status-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 20px;
            margin-top: 10px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #111827;
        }
        .message {
            background: {{ $status === 'approved' ? '#ecfdf5' : '#fef2f2' }};
            border-left: 4px solid {{ $status === 'approved' ? '#10b981' : '#ef4444' }};
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .message p {
            margin: 0;
            color: {{ $status === 'approved' ? '#065f46' : '#991b1b' }};
        }
        .details-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #6b7280;
            font-size: 14px;
        }
        .detail-value {
            color: #111827;
            font-weight: 600;
            font-size: 14px;
        }
        .response-box {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .response-box h3 {
            margin: 0 0 10px 0;
            color: #9a3412;
            font-size: 14px;
            font-weight: 600;
        }
        .response-box p {
            margin: 0;
            color: #7c2d12;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            background: {{ $status === 'approved' ? '#10b981' : '#3b82f6' }};
            color: #ffffff;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
            text-align: center;
        }
        .footer {
            background: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        .footer a {
            color: #3b82f6;
            text-decoration: none;
        }
        .warning {
            background: #fffbeb;
            border: 1px solid #fbbf24;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .warning p {
            margin: 0;
            color: #78350f;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Refund Request Update</h1>
            <div class="status-badge">{{ ucfirst($status) }}</div>
        </div>

        <div class="content">
            <p class="greeting">Dear {{ $userName }},</p>

            @if($status === 'approved')
                <div class="message">
                    <p><strong>✓ Your refund request has been approved!</strong></p>
                </div>

                <p>Great news! {{ $charityName }} has approved your refund request for your donation to <strong>{{ $campaignTitle }}</strong>.</p>

                <div class="warning">
                    <p><strong>Next Steps:</strong> The charity will process your refund directly. Please expect to receive your refund through the same payment method you used for the original donation within 5-7 business days. If you don't receive it, please contact the charity directly.</p>
                </div>
            @else
                <div class="message">
                    <p><strong>✗ Your refund request has been denied</strong></p>
                </div>

                <p>{{ $charityName }} has reviewed your refund request for your donation to <strong>{{ $campaignTitle }}</strong> and unfortunately, they are unable to approve it at this time.</p>
            @endif

            <div class="details-box">
                <div class="detail-row">
                    <span class="detail-label">Campaign/Project</span>
                    <span class="detail-value">{{ $campaignTitle }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Charity</span>
                    <span class="detail-value">{{ $charityName }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Donation Amount</span>
                    <span class="detail-value">₱{{ $amount }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Donation Date</span>
                    <span class="detail-value">{{ $donationDate }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Refund ID</span>
                    <span class="detail-value">#{{ $refundId }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Reviewed On</span>
                    <span class="detail-value">{{ $reviewedAt }}</span>
                </div>
            </div>

            @if($charityResponse)
                <div class="response-box">
                    <h3>Message from {{ $charityName }}:</h3>
                    <p>{{ $charityResponse }}</p>
                </div>
            @endif

            @if($status === 'denied')
                <div class="warning">
                    <p>If you believe this decision was made in error or have questions, please contact {{ $charityName }} directly through the CharityHub platform.</p>
                </div>
            @endif

            <div style="text-align: center;">
                <a href="{{ $dashboardUrl }}" class="button">View My Refund Requests</a>
            </div>

            <p style="margin-top: 30px; font-size: 14px; color: #6b7280;">
                Thank you for using CharityHub to make a difference.
            </p>
        </div>

        <div class="footer">
            <p>
                This is an automated email from CharityHub.<br>
                For questions about this refund, please contact <a href="{{ $charityContactUrl }}">{{ $charityName }}</a>.<br>
                <a href="{{ config('app.frontend_url') }}">Visit CharityHub</a> | <a href="{{ config('app.frontend_url') }}/contact">Contact Support</a>
            </p>
        </div>
    </div>
</body>
</html>
