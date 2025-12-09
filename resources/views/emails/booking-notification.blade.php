<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Notification - Agapay</title>
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
        .booking-details {
            background-color: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid #8B0000;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
            margin-top: 10px;
        }
        .status-pending { background-color: #FEF3C7; color: #92400E; }
        .status-accepted { background-color: #DBEAFE; color: #1E40AF; }
        .status-completed { background-color: #D1FAE5; color: #065F46; }
        .status-declined { background-color: #FEE2E2; color: #991B1B; }
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
        <h1>Agapay Booking Notification</h1>
</div>
    
    <div class="content">
        <h2>Hello {{ $recipientName }},</h2>
        
        @if($type === 'created')
            <p>You have received a new booking request!</p>
        @elseif($type === 'accepted')
            <p>Your booking request has been <strong>accepted</strong>!</p>
        @elseif($type === 'declined')
            <p>Unfortunately, your booking request has been <strong>declined</strong>.</p>
        @elseif($type === 'completed')
            <p>Your booking has been marked as <strong>completed</strong>!</p>
        @else
            <p>Your booking status has been updated.</p>
        @endif

        <div class="booking-details">
            <h3>Booking Details</h3>
            <p><strong>Order ID:</strong> #{{ $order->id }}</p>
            <p><strong>Service/Product:</strong> {{ $order->offering_name ?? 'N/A' }}</p>
            
            @if($type === 'created')
                <p><strong>Customer:</strong> {{ $order->customer->name ?? 'N/A' }}</p>
                <p><strong>Customer Email:</strong> {{ $order->customer->email ?? 'N/A' }}</p>
            @else
                <p><strong>Provider:</strong> {{ $order->provider->name ?? 'N/A' }}</p>
            @endif

            @if($order->scheduled_date)
                <p><strong>Scheduled Date:</strong> {{ \Carbon\Carbon::parse($order->scheduled_date)->format('F d, Y') }}</p>
            @endif

            @if($order->scheduled_time)
                <p><strong>Scheduled Time:</strong> {{ \Carbon\Carbon::parse($order->scheduled_time)->format('g:i A') }}</p>
            @endif

            @if($order->total_amount > 0)
                <p><strong>Total Amount:</strong> ₱{{ number_format($order->total_amount, 2) }}</p>
            @endif

            @if($order->notes)
                <p><strong>Notes:</strong> {{ $order->notes }}</p>
            @endif

            <span class="status-badge status-{{ $order->status }}">
                {{ ucfirst($order->status) }}
            </span>
        </div>

        <p>You can view more details and manage your bookings by logging into your Agapay account.</p>
        
        <p style="margin-top: 20px;">
            <a href="{{ url('/bookings') }}" style="background-color: #8B0000; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
                View Bookings
            </a>
        </p>
    </div>

    <div class="footer">
        <p>This is an automated notification from Agapay.</p>
        <p>Please do not reply to this email.</p>
    </div>
</body>
</html>
