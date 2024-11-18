<?php

namespace App\Wishlist\Infrastructure\Routes;

use App\Shared\Infrastructure\Routing\RouteCollection;

class WishlistRoutes extends RouteCollection
{
    public function register(): void
    {
        $this->router->get('/wishlist', function() {
            $this->container->get('wishlist_controller')->index();
        });

        $this->router->post('/wishlist/add', function() {
            $this->container->get('wishlist_controller')->add();
        });

        $this->router->post('/wishlist/remove', function() {
            $this->container->get('wishlist_controller')->remove();
        });

        $this->router->post('/wishlist/move-to-cart', function() {
            $this->container->get('wishlist_controller')->moveToCart();
        });

        $this->router->post('/wishlist/clear', function() {
            $this->container->get('wishlist_controller')->clear();
        });
    }
} 