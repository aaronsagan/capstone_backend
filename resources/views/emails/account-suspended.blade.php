<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      background: #f6f7f9;
      padding: 24px;
      margin: 0;
      line-height: 1.6;
    }
    .container {
      max-width: 600px;
      margin: 0 auto;
      background: #ffffff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .header {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      color: white;
      padding: 32px 24px;
      text-align: center;
    }
    .header-icon {
      font-size: 48px;
      margin-bottom: 12px;
    }
    .header-title {
      font-size: 24px;
      font-weight: 700;
      margin: 0;
      color: white;
    }
    .content {
      padding: 32px 24px;
    }
    .greeting {
      font-size: 16px;
      color: #1f2937;
      margin-bottom: 16px;
    }
    .message {
      font-size: 15px;
      color: #4b5563;
      margin-bottom: 24px;
      line-height: 1.8;
    }
    .alert-box {
      background: #fef2f2;
      border-left: 4px solid #ef4444;
      padding: 16px;
      margin: 20px 0;
      border-radius: 6px;
    }
    .alert-title {
      font-weight: 700;
      color: #991b1b;
      margin: 0 0 8px 0;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .alert-text {
      color: #7f1d1d;
      margin: 0;
      font-size: 14px;
    }
    .info-grid {
      background: #f9fafb;
      border-radius: 8px;
      padding: 20px;
      margin: 24px 0;
    }
    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 12px 0;
      border-bottom: 1px solid #e5e7eb;
    }
    .info-row:last-child {
      border-bottom: none;
    }
    .info-label {
      font-weight: 600;
      color: #6b7280;
      font-size: 14px;
    }
    .info-value {
      font-weight: 700;
      color: #1f2937;
      font-size: 14px;
    }
    .info-value.highlight {
      color: #dc2626;
      font-size: 16px;
    }
    .reason-box {
      background: #fffbeb;
      border: 1px solid #fde68a;
      border-radius: 8px;
      padding: 16px;
      margin: 20px 0;
    }
    .reason-label {
      font-weight: 700;
      color: #92400e;
      margin: 0 0 8px 0;
      font-size: 13px;
      text-transform: uppercase;
    }
    .reason-text {
      color: #78350f;
      margin: 0;
      font-size: 14px;
      font-style: italic;
    }
    .countdown {
      text-align: center;
      margin: 24px 0;
      padding: 20px;
      background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
      border-radius: 8px;
    }
    .countdown-label {
      font-size: 12px;
      color: #991b1b;
      font-weight: 600;
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .countdown-value {
      font-size: 36px;
      font-weight: 800;
      color: #dc2626;
      margin: 0;
    }
    .countdown-unit {
      font-size: 14px;
      color: #991b1b;
      font-weight: 600;
      margin-top: 4px;
    }
    .footer {
      background: #f9fafb;
      padding: 24px;
      text-align: center;
      font-size: 13px;
      color: #6b7280;
      border-top: 1px solid #e5e7eb;
    }
    .footer-note {
      margin: 8px 0 0 0;
      font-size: 12px;
      color: #9ca3af;
    }
    .btn {
      display: inline-block;
      padding: 12px 24px;
      background: #dc2626;
      color: white;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 600;
      margin: 16px 0;
    }
    @media only screen and (max-width: 600px) {
      .info-row {
        flex-direction: column;
        gap: 4px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Header -->
    <div class="header">
      <div class="header-icon">🚫</div>
      <h1 class="header-title">Account Suspended</h1>
    </div>

    <!-- Content -->
    <div class="content">
      <p class="greeting">Hello <strong>{{ $user_name }}</strong>,</p>
      
      <p class="message">
        Your account has been temporarily suspended due to a violation of our community guidelines and terms of service.
      </p>

      <!-- Alert Box -->
      <div class="alert-box">
        <h3 class="alert-title">⚠️ Access Restricted</h3>
        <p class="alert-text">
          You will not be able to log in or access your account until the suspension period ends.
        </p>
      </div>

      <!-- Suspension Details -->
      <div class="info-grid">
        <div class="info-row">
          <span class="info-label">Suspension Status:</span>
          <span class="info-value highlight">ACTIVE</span>
        </div>
        <div class="info-row">
          <span class="info-label">Severity Level:</span>
          <span class="info-value">{{ strtoupper($severity ?? 'MEDIUM') }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Suspended On:</span>
          <span class="info-value">{{ $suspended_on }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Suspension Ends:</span>
          <span class="info-value highlight">{{ $suspended_until }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Duration:</span>
          <span class="info-value">{{ $penalty_days }} {{ $penalty_days == 1 ? 'Day' : 'Days' }}</span>
        </div>
      </div>

      <!-- Countdown -->
      <div class="countdown">
        <div class="countdown-label">Time Remaining</div>
        <div class="countdown-value">{{ $days_remaining }}</div>
        <div class="countdown-unit">{{ $days_remaining == 1 ? 'Day' : 'Days' }} {{ $hours_remaining }} {{ $hours_remaining == 1 ? 'Hour' : 'Hours' }}</div>
      </div>

      <!-- Reason -->
      <div class="reason-box">
        <h4 class="reason-label">📋 Reason for Suspension</h4>
        <p class="reason-text">{{ $reason }}</p>
      </div>

      <p class="message">
        <strong>What happens next?</strong><br>
        • Your account will be automatically reactivated on <strong>{{ $suspended_until }}</strong><br>
        • You will receive a notification when your account is reactivated<br>
        • All your data and information will remain intact<br>
        • Please review our community guidelines to avoid future violations
      </p>

      <p class="message">
        If you believe this suspension was made in error, please contact our support team with your account details and explanation.
      </p>
    </div>

    <!-- Footer -->
    <div class="footer">
      <p style="margin: 0; font-weight: 600;">CharityHub Support Team</p>
      <p class="footer-note">&copy; {{ date('Y') }} CharityHub. All rights reserved.</p>
      <p class="footer-note">This is an automated system notification.</p>
    </div>
  </div>
</body>
</html>
