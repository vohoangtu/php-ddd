<?php

namespace App\Cart\Infrastructure\Routes;

use App\Shared\Infrastructure\Routing\RouteCollection;

class CartRoutes extends RouteCollection
{
    public function register(): void
    {
        $this->router->get('/cart', function() {
            $this->container->get('cart_controller')->index();
        });

        $this->router->post('/cart/add', function() {
            $this->container->get('cart_controller')->add();
        });

        $this->router->post('/cart/update', function() {
            $this->container->get('cart_controller')->update();
        });

        $this->router->post('/cart/remove', function() {
            $this->container->get('cart_controller')->remove();
        });

        $this->router->post('/cart/clear', function() {
            $this->container->get('cart_controller')->clear();
        });
    }
} 