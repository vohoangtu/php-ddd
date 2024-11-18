<?php

namespace App\Payment\Application;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use App\Payment\Domain\PaymentMethod;
use App\Payment\Domain\PaymentStatus;

class PaymentService
{
    private PayPalHttpClient $paypalClient;
    
    public function __construct()
    {
        // Initialize Stripe
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        
        // Initialize PayPal
        $environment = new SandboxEnvironment(
            $_ENV['PAYPAL_CLIENT_ID'],
            $_ENV['PAYPAL_CLIENT_SECRET']
        );
        $this->paypalClient = new PayPalHttpClient($environment);
    }

    public function createStripePaymentIntent(float $amount, string $currency = 'USD'): array
    {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $this->convertToCents($amount),
                'currency' => $currency,
                'payment_method_types' => ['card'],
                'metadata' => [
                    'integration_check' => 'accept_a_payment',
                ]
            ]);

            return [
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to create payment intent: ' . $e->getMessage());
        }
    }

    public function createPayPalOrder(float $amount, string $currency = 'USD'): array
    {
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        
        $request->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => $currency,
                    'value' => number_format($amount, 2, '.', '')
                ]
            ]],
            'application_context' => [
                'return_url' => $_ENV['APP_URL'] . '/checkout/paypal/success',
                'cancel_url' => $_ENV['APP_URL'] . '/checkout/paypal/cancel'
            ]
        ];

        try {
            $response = $this->paypalClient->execute($request);
            
            return [
                'orderId' => $response->result->id,
                'approvalUrl' => $this->getPayPalApprovalUrl($response)
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to create PayPal order: ' . $e->getMessage());
        }
    }

    public function recordPayment(
        int $orderId, 
        string $paymentMethod,
        float $amount,
        string $transactionId,
        string $status = PaymentStatus::COMPLETED
    ): void {
        DB::table('payments')->insert([
            'order_id' => $orderId,
            'payment_method' => $paymentMethod,
            'amount' => $amount,
            'transaction_id' => $transactionId,
            'status' => $status,
            'created_at' => now()
        ]);

        if ($status === PaymentStatus::COMPLETED) {
            $this->updateOrderStatus($orderId, 'paid');
        }
    }

    public function verifyStripePayment(string $paymentIntentId): bool
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            return $paymentIntent->status === 'succeeded';
        } catch (\Exception $e) {
            return false;
        }
    }

    public function capturePayPalOrder(string $orderId): bool
    {
        $request = new OrdersCaptureRequest($orderId);
        
        try {
            $response = $this->paypalClient->execute($request);
            return $response->result->status === 'COMPLETED';
        } catch (\Exception $e) {
            return false;
        }
    }

    private function convertToCents(float $amount): int
    {
        return (int)($amount * 100);
    }

    private function getPayPalApprovalUrl($response): string
    {
        foreach ($response->result->links as $link) {
            if ($link->rel === 'approve') {
                return $link->href;
            }
        }
        throw new \Exception('PayPal approval URL not found');
    }

    private function updateOrderStatus(int $orderId, string $status): void
    {
        DB::table('orders')
            ->where('id', $orderId)
            ->update([
                'status' => $status,
                'updated_at' => now()
            ]);
    }
} 