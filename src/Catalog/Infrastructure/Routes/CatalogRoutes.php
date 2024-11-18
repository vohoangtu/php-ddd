<?php

namespace App\Catalog\Infrastructure\Routes;

use Illuminate\Routing\Router;

class CatalogRoutes
{
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function register()
    {
        $this->router->get('/search', function() {
            $this->container->get('search_controller')->index();
        });
    }
} 