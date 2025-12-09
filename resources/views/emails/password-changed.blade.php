<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Changed - Agapay</title>
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
        <h1>Password Changed</h1>
    </div>
    
    <div class="content">
        <h2>Hello {{ $userName }},</h2>
        
        <p>This is to confirm that your password has been successfully changed.</p>

        <div class="alert-box">
            <p><strong>Security Notice:</strong></p>
            <p>If you did not make this change, please contact us immediately and change your password again.</p>
        </div>

        <p><strong>Account Details:</strong></p>
        <ul>
            <li>Email: {{ $userEmail }}</li>
            <li>Time: {{ $changedAt }}</li>
        </ul>

        <p>For your security, if you did not make this change, please:</p>
        <ol>
            <li>Change your password immediately</li>
            <li>Review your account security settings</li>
            <li>Contact support if you notice any suspicious activity</li>
        </ol>
    </div>

    <div class="footer">
        <p>This is an automated notification from Agapay.</p>
        <p>Please do not reply to this email.</p>
    </div>
</body>
</html>

