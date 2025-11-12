<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 20px; text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 600;">
                                CharityHub
                            </h1>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="margin: 0 0 20px; color: #1a202c; font-size: 24px; font-weight: 600;">
                                Verify Your Email Address
                            </h2>
                            
                            <p style="margin: 0 0 20px; color: #4a5568; font-size: 16px; line-height: 1.6;">
                                Hello {{ $userName }},
                            </p>
                            
                            <p style="margin: 0 0 30px; color: #4a5568; font-size: 16px; line-height: 1.6;">
                                Thank you for registering with CharityHub! To complete your registration and start making a difference, please verify your email address using the code below:
                            </p>

                            <!-- Verification Code Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 0 0 30px;">
                                <tr>
                                    <td style="background-color: #f7fafc; border: 2px dashed #cbd5e0; border-radius: 8px; padding: 30px; text-align: center;">
                                        <p style="margin: 0 0 10px; color: #718096; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                                            Your Verification Code
                                        </p>
                                        <div style="margin: 0; color: #667eea; font-size: 42px; font-weight: 700; letter-spacing: 8px; font-family: 'Courier New', monospace;">
                                            {{ $code }}
                                        </div>
                                        <p style="margin: 10px 0 0; color: #a0aec0; font-size: 13px;">
                                            This code expires in {{ $expiresIn }} minutes
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Alternative Method -->
                            <p style="margin: 0 0 20px; color: #4a5568; font-size: 16px; line-height: 1.6;">
                                Alternatively, you can click the button below to verify your email:
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 0 0 30px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $verifyUrl }}" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; padding: 14px 40px; border-radius: 6px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 6px rgba(102, 126, 234, 0.4);">
                                            Verify My Email
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Security Notice -->
                            <div style="background-color: #fffaf0; border-left: 4px solid #f6ad55; padding: 15px; margin: 0 0 20px; border-radius: 4px;">
                                <p style="margin: 0; color: #744210; font-size: 14px; line-height: 1.5;">
                                    <strong>🔒 Security Tip:</strong> If you didn't create an account with CharityHub, please ignore this email or contact our support team.
                                </p>
                            </div>

                            <!-- Additional Info -->
                            <p style="margin: 0 0 10px; color: #718096; font-size: 14px; line-height: 1.6;">
                                <strong>Need a new code?</strong> If your code has expired, you can request a new one from the verification page.
                            </p>
                            
                            <p style="margin: 0; color: #718096; font-size: 14px; line-height: 1.6;">
                                <strong>Maximum attempts:</strong> You have 5 attempts to enter the correct code before needing to request a new one.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 40px; background-color: #f7fafc; border-radius: 0 0 8px 8px; text-align: center;">
                            <p style="margin: 0 0 10px; color: #718096; font-size: 14px;">
                                This email was sent to <strong>{{ $userEmail }}</strong>
                            </p>
                            <p style="margin: 0 0 15px; color: #a0aec0; font-size: 13px;">
                                CharityHub - Making Giving Simple
                            </p>
                            <p style="margin: 0; color: #cbd5e0; font-size: 12px;">
                                © {{ date('Y') }} CharityHub. All rights reserved.
                            </p>
                            <p style="margin: 10px 0 0; color: #a0aec0; font-size: 12px;">
                                Need help? Contact us at <a href="mailto:charityhub25@gmail.com" style="color: #667eea; text-decoration: none;">charityhub25@gmail.com</a>
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- Plain Text Alternative Note -->
                <table width="600" cellpadding="0" cellspacing="0" style="margin-top: 20px;">
                    <tr>
                        <td style="padding: 0 20px; text-align: center;">
                            <p style="margin: 0; color: #a0aec0; font-size: 12px; line-height: 1.5;">
                                If the button doesn't work, copy and paste this link into your browser:<br>
                                <a href="{{ $verifyUrl }}" style="color: #667eea; text-decoration: none; word-break: break-all;">{{ $verifyUrl }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
