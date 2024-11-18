<?php
namespace App\Notification\Application;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Database\Capsule\Manager as DB;

class EmailNotificationService
{
    private PHPMailer $mailer;
    
    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        
        // Configure SMTP
        $this->mailer->isSMTP();
        $this->mailer->Host = $_ENV['SMTP_HOST'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $_ENV['SMTP_USERNAME'];
        $this->mailer->Password = $_ENV['SMTP_PASSWORD'];
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = $_ENV['SMTP_PORT'];
        
        // Set sender
        $this->mailer->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
    }

    public function sendWelcomeEmail(string $email, string $name): void
    {
        try {
            $this->mailer->addAddress($email, $name);
            $this->mailer->isHTML(true);
            
            $this->mailer->Subject = 'Welcome to Our Store!';
            $this->mailer->Body = $this->renderTemplate('emails.welcome', [
                'name' => $name
            ]);
            
            $this->mailer->send();
        } catch (Exception $e) {
            $this->logError('Welcome email failed', $e, ['email' => $email]);
        }
    }

    public function sendOrderConfirmation(int $orderId): void
    {
        try {
            $order = $this->getOrderDetails($orderId);
            if (!$order) {
                throw new \Exception('Order not found');
            }

            $this->mailer->addAddress($order->email, $order->customer_name);
            $this->mailer->isHTML(true);
            
            $this->mailer->Subject = "Order Confirmation #{$orderId}";
            $this->mailer->Body = $this->renderTemplate('emails.order_confirmation', [
                'order' => $order,
                'items' => $this->getOrderItems($orderId)
            ]);
            
            $this->mailer->send();
        } catch (Exception $e) {
            $this->logError('Order confirmation email failed', $e, ['order_id' => $orderId]);
        }
    }

    public function sendShipmentNotification(int $orderId, array $shipmentDetails): void
    {
        try {
            $order = $this->getOrderDetails($orderId);
            if (!$order) {
                throw new \Exception('Order not found');
            }

            $this->mailer->addAddress($order->email, $order->customer_name);
            $this->mailer->isHTML(true);
            
            $this->mailer->Subject = "Your Order #{$orderId} Has Been Shipped";
            $this->mailer->Body = $this->renderTemplate('emails.shipment_notification', [
                'order' => $order,
                'shipment' => $shipmentDetails
            ]);
            
            $this->mailer->send();
        } catch (Exception $e) {
            $this->logError('Shipment notification email failed', $e, ['order_id' => $orderId]);
        }
    }

    public function sendAbandonedCartReminder(int $userId): void
    {
        try {
            $user = DB::table('users')->find($userId);
            if (!$user || !$user->email) {
                throw new \Exception('User not found');
            }

            $cartItems = $this->getAbandonedCartItems($userId);
            if (empty($cartItems)) {
                return;
            }

            $this->mailer->addAddress($user->email, $user->name);
            $this->mailer->isHTML(true);
            
            $this->mailer->Subject = 'Complete Your Purchase';
            $this->mailer->Body = $this->renderTemplate('emails.abandoned_cart', [
                'name' => $user->name,
                'items' => $cartItems
            ]);
            
            $this->mailer->send();
        } catch (Exception $e) {
            $this->logError('Abandoned cart email failed', $e, ['user_id' => $userId]);
        }
    }

    public function sendBackInStockNotification(int $productId): void
    {
        try {
            $subscribers = $this->getStockNotificationSubscribers($productId);
            $product = DB::table('products')->find($productId);

            if (!$product || empty($subscribers)) {
                return;
            }

            foreach ($subscribers as $subscriber) {
                $this->mailer->clearAddresses();
                $this->mailer->addAddress($subscriber->email, $subscriber->name);
                $this->mailer->isHTML(true);
                
                $this->mailer->Subject = "{$product->name} Is Back In Stock!";
                $this->mailer->Body = $this->renderTemplate('emails.back_in_stock', [
                    'product' => $product,
                    'name' => $subscriber->name
                ]);
                
                $this->mailer->send();
            }

            // Clear notification list
            DB::table('stock_notifications')
                ->where('product_id', $productId)
                ->delete();
        } catch (Exception $e) {
            $this->logError('Back in stock notification failed', $e, ['product_id' => $productId]);
        }
    }

    private function renderTemplate(string $template, array $data = []): string
    {
        ob_start();
        extract($data);
        include __DIR__ . "/../../../views/{$template}.php";
        return ob_get_clean();
    }

    private function getOrderDetails(int $orderId): ?object
    {
        return DB::table('orders')
            ->select('orders.*', 'users.email', 'users.name as customer_name')
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->where('orders.id', $orderId)
            ->first();
    }

    private function getOrderItems(int $orderId): array
    {
        return DB::table('order_items')
            ->select('order_items.*', 'products.name', 'products.image')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->where('order_id', $orderId)
            ->get()
            ->all();
    }

    private function getAbandonedCartItems(int $userId): array
    {
        return DB::table('cart_items')
            ->select('cart_items.*', 'products.name', 'products.image', 'products.price')
            ->leftJoin('products', 'products.id', '=', 'cart_items.product_id')
            ->where('user_id', $userId)
            ->where('cart_items.created_at', '<', now()->subHours(24))
            ->get()
            ->all();
    }

    private function getStockNotificationSubscribers(int $productId): array
    {
        return DB::table('stock_notifications')
            ->select('users.email', 'users.name')
            ->leftJoin('users', 'users.id', '=', 'stock_notifications.user_id')
            ->where('product_id', $productId)
            ->get()
            ->all();
    }

    private function logError(string $message, \Exception $exception, array $context = []): void
    {
        error_log(sprintf(
            "[Email Error] %s: %s\nContext: %s",
            $message,
            $exception->getMessage(),
            json_encode($context)
        ));
    }
}