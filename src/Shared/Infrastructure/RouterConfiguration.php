<?php

namespace App\Shared\Infrastructure;

use Bramus\Router\Router;

class RouterConfiguration
{
    private Router $router;
    private Container $container;

    public function __construct(Router $router, Container $container)
    {
        $this->router = $router;
        $this->container = $container;
    }

    public function configure(): void
    {
        $this->configureProductRoutes();
        $this->configureCartRoutes();
        $this->configureCheckoutRoutes();
        $this->configureOrderRoutes();
        $this->configureAuthRoutes();
        $this->configureAdminRoutes();
    }

    private function configureProductRoutes(): void
    {
        $this->router->get('/', function() {
            $this->container->get('product_controller')->index();
        });

        $this->router->get('/products/(\d+)', function($id) {
            $this->container->get('product_controller')->show($id);
        });
    }

    private function configureCartRoutes(): void
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

    private function configureCheckoutRoutes(): void
    {
        $this->router->get('/checkout', function() {
            $this->container->get('checkout_controller')->showCheckoutForm();
        });

        $this->router->post('/checkout', function() {
            $this->container->get('checkout_controller')->processCheckout();
        });
    }

    private function configureOrderRoutes(): void
    {
        $this->router->get('/order/success/(\d+)', function($orderId) {
            echo $this->container->get('blade')->make('order.success', ['orderId' => $orderId])->render();
        });

        $this->router->get('/order/(\d+)', function($orderId) {
            $order = $this->container->get('order_repository')->findById($orderId);
            if (!$order) {
                header('Location: /');
                exit;
            }
            echo $this->container->get('blade')->make('order.detail', ['order' => $order])->render();
        });
    }

    private function configureAuthRoutes(): void
    {
        $this->router->get('/login', function() {
            $this->container->get('auth_controller')->showLoginForm();
        });

        $this->router->post('/login', function() {
            $this->container->get('auth_controller')->login();
        });

        $this->router->get('/logout', function() {
            $this->container->get('auth_controller')->logout();
        });
    }

    private function configureAdminRoutes(): void
    {
        $this->router->before('GET|POST', '/admin/.*', function() {
            $this->container->get('admin_middleware')->handle();
        });

        $this->router->get('/admin/dashboard', function() {
            $this->container->get('admin_controller')->dashboard();
        });

        $this->router->get('/admin/products', function() {
            $this->container->get('admin_controller')->products();
        });

        $this->router->get('/admin/orders', function() {
            $this->container->get('admin_controller')->orders();
        });

        $this->router->get('/admin/products/create', function() {
            $this->container->get('admin_controller')->createProduct();
        });

        $this->router->post('/admin/products', function() {
            $this->container->get('admin_controller')->storeProduct();
        });

        $this->router->get('/admin/products/(\d+)/edit', function($id) {
            $this->container->get('admin_controller')->editProduct($id);
        });

        $this->router->post('/admin/products/(\d+)', function($id) {
            $this->container->get('admin_controller')->updateProduct($id);
        });

        $this->router->post('/admin/products/(\d+)/delete', function($id) {
            $this->container->get('admin_controller')->deleteProduct($id);
        });
    }
} 