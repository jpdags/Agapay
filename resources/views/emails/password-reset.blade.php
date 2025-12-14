<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - Agapay</title>
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
            background-color: #8B0000;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
        }
        .alert-box {
            background-color: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .button {
            display: inline-block;
            background-color: #8B0000;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Password Reset Request</h1>
    </div>
    
    <div class="content">
        <h2>Hello {{ $userName }},</h2>
        
        <p>We received a request to reset your password for your Agapay account.</p>

        <div class="alert-box">
            <p><strong>Security Notice:</strong></p>
            <p>If you did not request a password reset, please ignore this email. Your password will remain unchanged.</p>
        </div>

        <p>To reset your password, click the button below:</p>
        
        <div style="text-align: center;">
            <a href="{{ $resetUrl }}" class="button">Reset Password</a>
        </div>

        <p>Or copy and paste this link into your browser:</p>
        <p style="word-break: break-all; color: #666; font-size: 12px;">{{ $resetUrl }}</p>

        <p><strong>Important:</strong></p>
        <ul>
            <li>This link will expire in {{ $expiresIn }}</li>
            <li>For security reasons, this link can only be used once</li>
            <li>If you didn't request this, you can safely ignore this email</li>
        </ul>
    </div>

    <div class="footer">
        <p>This is an automated notification from Agapay.</p>
        <p>Please do not reply to this email.</p>
    </div>
</body>
</html>

