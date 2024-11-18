<?php

namespace App\Shared\Infrastructure\Routing;

use Bramus\Router\Router;
use App\Shared\Infrastructure\Container;

abstract class RouteCollection
{
    protected Router $router;
    protected Container $container;

    public function __construct(Router $router, Container $container)
    {
        $this->router = $router;
        $this->container = $container;
    }

    abstract public function register(): void;
} 