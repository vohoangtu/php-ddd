<?php

namespace App\Shared\Infrastructure\Routing;

use Bramus\Router\Router;
use App\Shared\Infrastructure\Container;
use App\Catalog\Infrastructure\Routes\ProductRoutes;
use App\Catalog\Infrastructure\Routes\CartRoutes;
use App\Order\Infrastructure\Routes\OrderRoutes;
use App\User\Infrastructure\Routes\AuthRoutes;
use App\Admin\Infrastructure\Routes\AdminRoutes;

class RouterRegistry
{
    private Router $router;
    private Container $container;
    private array $routeCollections;

    public function __construct(Router $router, Container $container)
    {
        $this->router = $router;
        $this->container = $container;
        $this->routeCollections = [
            ProductRoutes::class,
            CartRoutes::class,
            OrderRoutes::class,
            AuthRoutes::class,
            AdminRoutes::class
        ];
    }

    public function registerRoutes(): void
    {
        foreach ($this->routeCollections as $routeCollectionClass) {
            $routeCollection = new $routeCollectionClass($this->router, $this->container);
            $routeCollection->register();
        }
    }
} 