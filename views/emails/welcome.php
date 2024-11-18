<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px 0; }
        .content { background: #f9f9f9; padding: 20px; border-radius: 5px; }
        .button { 
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Our Store!</h1>
        </div>
        <div class="content">
            <p>Dear <?php echo $name; ?>,</p>
            <p>Thank you for joining our online store! We're excited to have you as a member.</p>
            <p>Here are some things you can do:</p>
            <ul>
                <li>Browse our latest products</li>
                <li>Create your wishlist</li>
                <li>Track your orders</li>
                <li>Get personalized recommendations</li>
            </ul>
            <p style="text-align: center; margin-top: 30px;">
                <a href="<?php echo $_ENV['APP_URL']; ?>/products" class="button">
                    Start Shopping
                </a>
            </p>
        </div>
    </div>
</body>
</html> 