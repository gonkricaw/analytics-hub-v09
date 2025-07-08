<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - Analytics Hub</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #0E0E44 0%, #FF7A00 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            background: white;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
        }
        .message {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
            color: #555;
        }
        .button {
            display: inline-block;
            background: #FF7A00;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            background: #e66a00;
        }
        .security-info {
            background: #f8f9fa;
            border-left: 4px solid #FF7A00;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .security-info h3 {
            margin-top: 0;
            color: #FF7A00;
            font-size: 16px;
        }
        .security-info ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .security-info li {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 14px;
            color: #666;
        }
        .footer a {
            color: #FF7A00;
            text-decoration: none;
        }
        .expiry-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
            font-size: 14px;
        }
        .no-reply {
            background: #e9ecef;
            border: 1px solid #dee2e6;
            color: #6c757d;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
            font-size: 14px;
        }
        @media (max-width: 600px) {
            .container {
                padding: 10px;
            }
            .header, .content {
                padding: 20px;
            }
            .button {
                display: block;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Analytics Hub</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Password Reset Request</p>
        </div>

        <div class="content">
            <div class="greeting">
                Hello {{ $user->full_name }},
            </div>

            <div class="message">
                We received a request to reset your password for your Analytics Hub account. If you made this request, please click the button below to reset your password.
            </div>

            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="button">Reset Password</a>
            </div>

            <div class="expiry-warning">
                <strong>⏰ Important:</strong> This password reset link will expire in {{ $expiryMinutes }} minutes for security reasons.
            </div>

            <div class="security-info">
                <h3>🔒 Security Information</h3>
                <ul>
                    <li>This link can only be used once</li>
                    <li>The link expires in {{ $expiryMinutes }} minutes</li>
                    <li>If you didn't request this reset, please ignore this email</li>
                    <li>Your password will remain unchanged until you complete the reset process</li>
                </ul>
            </div>

            <div class="message">
                If you're having trouble clicking the button, copy and paste the URL below into your web browser:
            </div>

            <div style="word-break: break-all; color: #666; font-family: monospace; background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;">
                {{ $resetUrl }}
            </div>

            <div class="no-reply">
                <strong>Note:</strong> This is an automated message. Please do not reply to this email.
            </div>
        </div>

        <div class="footer">
            <p>If you have any questions or concerns, please contact our support team.</p>
            <p>&copy; {{ date('Y') }} Analytics Hub. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
