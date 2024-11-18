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
            <h1>Order Confirmation</h1>
            <p>Order #{{ $order->id }}</p>
        </div>
        
        <div class="content">
            <p>Dear {{ $order->customer_name }},</p>
            
            <p>Thank you for your order. We're pleased to confirm that we've received your order and it's being processed.</p>
            
            <h3>Order Details:</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>${{ number_format($item->price * $item->quantity, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2"><strong>Total:</strong></td>
                        <td><strong>${{ number_format($order->total_amount, 2) }}</strong></td>
                    </tr>
                </tfoot>
            </table>

            <p>You can track your order status by clicking the button below:</p>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $orderUrl }}" 
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