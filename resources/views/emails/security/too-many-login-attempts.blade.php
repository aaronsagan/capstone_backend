<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #10b981;
        }
        .alert-box {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .alert-title {
            font-size: 18px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 10px;
        }
        .info-box {
            background: #f0fdf4;
            border: 1px solid #10b981;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .button {
            display: inline-block;
            background: #10b981;
            color: #ffffff;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
        }
        .button:hover {
            background: #059669;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }
        .details-list {
            margin: 15px 0;
            padding-left: 20px;
        }
        .details-list li {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🛡️ CharityHub Security</div>
        </div>

        <div class="alert-box">
            <div class="alert-title">⚠️ Multiple Failed Login Attempts Detected</div>
            <p>Hi <strong>{{ $user->name }}</strong>,</p>
            <p>We detected <strong>{{ $attemptCount }} consecutive failed login attempts</strong> to your CharityHub account.</p>
        </div>

        <h3>🔒 Your Account Has Been Temporarily Locked</h3>
        <p>For your security, we've temporarily locked your account for <strong>{{ $lockDuration }} minutes</strong>.</p>

        <div class="info-box">
            <strong>Details:</strong>
            <ul class="details-list">
                <li><strong>Account:</strong> {{ $user->email }}</li>
                <li><strong>Failed Attempts:</strong> {{ $attemptCount }}</li>
                <li><strong>Lock Duration:</strong> {{ $lockDuration }} minutes</li>
                <li><strong>Time:</strong> {{ now()->format('F d, Y \a\t g:i A') }}</li>
            </ul>
        </div>

        <h3>🤔 Was This You?</h3>
        <p><strong>If this was you:</strong></p>
        <ul>
            <li>Please wait {{ $lockDuration }} minutes before attempting to log in again</li>
            <li>Double-check your password carefully</li>
            <li>If you've forgotten your password, use the "Forgot Password" link on the login page</li>
        </ul>

        <p><strong>If this wasn't you:</strong></p>
        <ul>
            <li>Someone may be trying to access your account</li>
            <li>We strongly recommend resetting your password immediately</li>
            <li>Review your account security settings</li>
            <li>Contact our support team if you have concerns</li>
        </ul>

        <center>
            <a href="{{ config('app.frontend_url') }}/auth/forgot-password" class="button">
                Reset Your Password
            </a>
        </center>

        <div class="info-box" style="background: #eff6ff; border-color: #3b82f6;">
            <strong>💡 Security Tips:</strong>
            <ul class="details-list">
                <li>Use a strong, unique password</li>
                <li>Never share your password with anyone</li>
                <li>Be cautious of phishing emails</li>
                <li>Enable two-factor authentication when available</li>
            </ul>
        </div>

        <p>Your account will automatically unlock after {{ $lockDuration }} minutes. If you continue to have trouble logging in, please contact our support team.</p>

        <div class="footer">
            <p><strong>Stay Safe,</strong><br>
            The CharityHub Security Team</p>
            <p style="margin-top: 15px; font-size: 11px; color: #9ca3af;">
                This is an automated security notification. Please do not reply to this email.<br>
                If you didn't attempt to log in, please contact us immediately at support@charityhub.com
            </p>
        </div>
    </div>
</body>
</html>
