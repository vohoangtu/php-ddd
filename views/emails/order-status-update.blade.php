<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8f9fa; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table th, .table td { padding: 10px; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order Status Update</h1>
            <p>Order #{{ $order->id }}</p>
        </div>
        
        <div class="content">
            <p>Dear {{ $order->customer_name }},</p>
            
            <p>The status of your order has been updated to: <strong>{{ ucfirst($order->status) }}</strong></p>
            
            @if($order->status === 'processing')
                <p>We're currently processing your order and will notify you once it's ready for shipping.</p>
            @elseif($order->status === 'completed')
                <p>Your order has been completed. Thank you for shopping with us!</p>
            @elseif($order->status === 'cancelled')
                <p>Your order has been cancelled. If you didn't request this cancellation, please contact our support team.</p>
            @endif
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ url("/order/{$order->id}") }}" 
                   style="background: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px;">
                    View Order Details
                </a>
            </div>
        </div>
        
        <div class="footer">
            <p>If you have any questions, please contact our support team.</p>
            <p>© {{ date('Y') }} Your Company. All rights reserved.</p>
        </div>
    </div>
</body>
</html> 