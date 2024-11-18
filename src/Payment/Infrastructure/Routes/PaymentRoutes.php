<?php

namespace App\Payment\Infrastructure\Routes;

use App\Shared\Infrastructure\Routing\RouteCollection;

class PaymentRoutes extends RouteCollection
{
    public function register(): void
    {
        // Checkout pages
        $this->router->get('/checkout/payment/(\d+)', function($orderId) {
            $this->container->get('payment_controller')->checkout($orderId);
        });

        // Stripe endpoints
        $this->router->post('/checkout/stripe/process', function() {
            $this->container->get('payment_controller')->processStripePayment();
        });

        $this->router->post('/webhook/stripe', function() {
            $this->container->get('payment_controller')->handleStripeWebhook();
        });

        // PayPal endpoints
        $this->router->post('/checkout/paypal/process', function() {
            $this->container->get('payment_controller')->processPayPalPayment();
        });

        $this->router->get('/checkout/paypal/success', function() {
            $this->container->get('payment_controller')->handlePayPalSuccess();
        });

        $this->router->get('/checkout/paypal/cancel', function() {
            header('Location: /checkout/error');
            exit;
        });
    }
} 