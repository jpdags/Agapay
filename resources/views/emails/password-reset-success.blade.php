<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Successful - Agapay</title>
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
        .success-box {
            background-color: #D1FAE5;
            border-left: 4px solid #10B981;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
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
        <h1>Password Reset Successful</h1>
    </div>
    
    <div class="content">
        <h2>Hello {{ $userName }},</h2>
        
        <p>Your password has been successfully reset.</p>

        <div class="success-box">
            <p><strong>✓ Password Reset Confirmed</strong></p>
            <p>Your Agapay account password was changed on {{ $resetAt }}.</p>
        </div>

        <p><strong>What to do next:</strong></p>
        <ul>
            <li>You can now log in with your new password</li>
            <li>If you did not make this change, please contact support immediately</li>
            <li>For security, consider enabling two-factor authentication if available</li>
        </ul>

        <p style="margin-top: 20px;">
            <a href="{{ url('/login') }}" style="background-color: #8B0000; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
                Log In Now
            </a>
        </p>
    </div>

    <div class="footer">
        <p>This is an automated notification from Agapay.</p>
        <p>Please do not reply to this email.</p>
    </div>
</body>
</html>

