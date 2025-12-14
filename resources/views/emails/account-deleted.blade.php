<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Deleted - Agapay</title>
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
            background-color: #FEE2E2;
            border-left: 4px solid #EF4444;
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
        <h1>Account Deletion Confirmation</h1>
    </div>
    
    <div class="content">
        <h2>Hello {{ $userName }},</h2>
        
        <p>This is to confirm that your Agapay account has been permanently deleted.</p>

        <div class="alert-box">
            <p><strong>Important:</strong></p>
            <p>All your account data, including bookings, orders, and profile information, has been permanently removed from our system.</p>
        </div>

        <p><strong>Account Details:</strong></p>
        <ul>
            <li>Email: {{ $userEmail }}</li>
            <li>Deleted at: {{ $deletedAt }}</li>
        </ul>

        <p>If you did not request this deletion, please contact our support team immediately.</p>

        <p>We're sorry to see you go. If you change your mind, you can always create a new account.</p>
    </div>

    <div class="footer">
        <p>This is an automated notification from Agapay.</p>
        <p>Please do not reply to this email.</p>
    </div>
</body>
</html>

