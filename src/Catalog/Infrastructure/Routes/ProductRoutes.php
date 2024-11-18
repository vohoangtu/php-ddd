<?php

namespace App\Catalog\Infrastructure\Routes;

use App\Shared\Infrastructure\Routing\RouteCollection;

class ProductRoutes extends RouteCollection
{
    public function register(): void
    {
        $this->router->get('/', function() {
            $this->container->get('product_controller')->index();
        });

        $this->router->get('/products/(\d+)', function($id) {
            $this->container->get('product_controller')->show($id);
        });
    }
} 