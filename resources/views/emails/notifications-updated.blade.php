<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Settings Updated - Agapay</title>
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
        .status-box {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .status-box.enabled {
            background-color: #D1FAE5;
            border-left: 4px solid #10B981;
        }
        .status-box.disabled {
            background-color: #FEE2E2;
            border-left: 4px solid #EF4444;
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
        <h1>Notification Settings Updated</h1>
    </div>
    
    <div class="content">
        <h2>Hello {{ $userName }},</h2>
        
        <p>Your notification settings have been updated.</p>

        <div class="status-box {{ $enabled ? 'enabled' : 'disabled' }}">
            <p><strong>Current Status:</strong></p>
            <p style="font-size: 18px; font-weight: bold;">
                Notifications are now <strong>{{ $enabled ? 'ENABLED' : 'DISABLED' }}</strong>
            </p>
        </div>

        @if($enabled)
            <p>You will now receive email notifications for:</p>
            <ul>
                <li>New booking requests</li>
                <li>Booking status updates</li>
                <li>Account security changes</li>
                <li>Important account updates</li>
            </ul>
        @else
            <p>You will no longer receive email notifications. You can re-enable them anytime in your account settings.</p>
        @endif

        <p><strong>Updated at:</strong> {{ $updatedAt }}</p>
    </div>

    <div class="footer">
        <p>This is an automated notification from Agapay.</p>
        <p>Please do not reply to this email.</p>
    </div>
</body>
</html>
