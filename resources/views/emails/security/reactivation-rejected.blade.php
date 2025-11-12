<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Reactivation Request Rejected</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .alert {
            background: #fee;
            border-left: 4px solid #f44;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Account Reactivation Request</h1>
    </div>
    
    <div class="content">
        <p>Hello {{ $userName }},</p>
        
        <div class="alert">
            <strong>⚠️ Request Rejected</strong>
            <p>Your account reactivation request has been reviewed and rejected by our administrator.</p>
        </div>
        
        <p><strong>Administrator's Notes:</strong></p>
        <p style="background: white; padding: 15px; border-radius: 5px; border-left: 3px solid #f44;">
            {{ $notes }}
        </p>
        
        <p>If you believe this decision was made in error or if you have additional information to provide, please contact our support team.</p>
        
        <div style="text-align: center;">
            <a href="{{ $supportUrl }}" class="button">Contact Support</a>
        </div>
        
        <p style="margin-top: 30px;">
            <strong>Account Details:</strong><br>
            Email: {{ $email }}<br>
            Status: Inactive
        </p>
    </div>
    
    <div class="footer">
        <p>This is an automated message from CharityHub.</p>
        <p>© {{ date('Y') }} CharityHub. All rights reserved.</p>
    </div>
</body>
</html>
