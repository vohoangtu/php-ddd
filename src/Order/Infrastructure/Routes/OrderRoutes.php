<?php

namespace App\Order\Infrastructure\Routes;

use App\Shared\Infrastructure\Routing\RouteCollection;

class OrderRoutes extends RouteCollection
{
    public function register(): void
    {
        $this->router->get('/checkout', function() {
            $this->container->get('checkout_controller')->showCheckoutForm();
        });

        $this->router->post('/checkout', function() {
            $this->container->get('checkout_controller')->processCheckout();
        });

        $this->router->get('/order/success/(\d+)', function($orderId) {
            echo $this->container->get('blade')->make('order.success', [
                'orderId' => $orderId
            ])->render();
        });

        $this->router->get('/order/(\d+)', function($orderId) {
            $order = $this->container->get('order_repository')->findById($orderId);
            if (!$order) {
                header('Location: /');
                exit;
            }
            echo $this->container->get('blade')->make('order.detail', [
                'order' => $order
            ])->render();
        });
    }
} 