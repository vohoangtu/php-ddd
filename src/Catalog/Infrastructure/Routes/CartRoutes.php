<?php

namespace App\Catalog\Infrastructure\Routes;

use App\Shared\Infrastructure\Routing\RouteCollection;

class CartRoutes extends RouteCollection
{
    public function register(): void
    {
        $this->router->get('/cart', function() {
            $this->container->get('cart_controller')->show();
        });

        $this->router->post('/cart/add/(\d+)', function($productId) {
            $quantity = $_POST['quantity'] ?? 1;
            $this->container->get('cart_controller')->add($productId, (int)$quantity);
        });

        $this->router->post('/cart/update/(\d+)', function($productId) {
            $quantity = $_POST['quantity'] ?? 1;
            $this->container->get('cart_controller')->update($productId, (int)$quantity);
        });

        $this->router->post('/cart/remove/(\d+)', function($productId) {
            $this->container->get('cart_controller')->remove($productId);
        });
    }
} 