<?php

namespace App\Payment\Application;

use App\Payment\Domain\PaymentMethod;
use Jenssegers\Blade\Blade;

class PaymentController
{
    private PaymentService $paymentService;
    private Blade $blade;

    public function __construct(PaymentService $paymentService, Blade $blade)
    {
        $this->paymentService = $paymentService;
        $this->blade = $blade;
    }

    public function checkout(int $orderId): void
    {
        $order = DB::table('orders')->find($orderId);
        if (!$order) {
            $_SESSION['error'] = 'Order not found';
            header('Location: /cart');
            exit;
        }

        echo $this->blade->make('checkout.payment', [
            'order' => $order,
            'stripeKey' => $_ENV['STRIPE_PUBLIC_KEY']
        ])->render();
    }

    public function processStripePayment(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $orderId = $data['orderId'] ?? null;
            $amount = $data['amount'] ?? null;

            if (!$orderId || !$amount) {
                throw new \Exception('Invalid payment data');
            }

            $intent = $this->paymentService->createStripePaymentIntent($amount);
            
            echo json_encode([
                'success' => true,
                'clientSecret' => $intent['clientSecret']
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function processPayPalPayment(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $orderId = $data['orderId'] ?? null;
            $amount = $data['amount'] ?? null;

            if (!$orderId || !$amount) {
                throw new \Exception('Invalid payment data');
            }

            $paypalOrder = $this->paymentService->createPayPalOrder($amount);
            
            echo json_encode([
                'success' => true,
                'approvalUrl' => $paypalOrder['approvalUrl']
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function handleStripeWebhook(): void
    {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $_ENV['STRIPE_WEBHOOK_SECRET']
            );

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;
                    $this->paymentService->recordPayment(
                        $paymentIntent->metadata->order_id,
                        PaymentMethod::STRIPE,
                        $paymentIntent->amount / 100,
                        $paymentIntent->id
                    );
                    break;
            }

            http_response_code(200);
        } catch (\Exception $e) {
            http_response_code(400);
            exit();
        }
    }

    public function handlePayPalSuccess(): void
    {
        $orderId = $_GET['token'] ?? null;
        
        if (!$orderId) {
            $_SESSION['error'] = 'Invalid payment data';
            header('Location: /checkout/error');
            exit;
        }

        try {
            if ($this->paymentService->capturePayPalOrder($orderId)) {
                $_SESSION['success'] = 'Payment completed successfully';
                header('Location: /checkout/success');
            } else {
                throw new \Exception('Payment capture failed');
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /checkout/error');
        }
        exit;
    }
} 