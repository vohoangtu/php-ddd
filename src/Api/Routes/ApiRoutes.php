<?php

namespace App\Api\Routes;

use App\Shared\Infrastructure\Routing\RouteCollection;
use App\Shared\Infrastructure\Api\ApiResponse;

class ApiRoutes extends RouteCollection
{
    public function register(): void
    {
        // API version prefix
        $this->router->mount('/api/v1', function() {
            
            // Apply API middleware to all routes
            $this->router->before('GET|POST|PUT|DELETE', '/.*', function() {
                $this->container->get('api_auth_middleware')->handle();
            });

            // Products
            $this->router->get('/products', function() {
                $this->container->get('product_api_controller')->index();
            });

            $this->router->get('/products/(\d+)', function($id) {
                $this->container->get('product_api_controller')->show($id);
            });

            // Orders
            $this->router->get('/orders', function() {
                $this->container->get('order_api_controller')->index();
            });

            $this->router->post('/orders', function() {
                $this->container->get('order_api_controller')->store();
            });

            // Categories
            $this->router->get('/categories', function() {
                $this->container->get('category_api_controller')->index();
            });

            // Handle 404 for API routes
            $this->router->set404(function() {
                ApiResponse::error('Endpoint not found', 404);
            });
        });
    }
} 