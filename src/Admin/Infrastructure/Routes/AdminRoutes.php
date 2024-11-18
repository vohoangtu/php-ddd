<?php

namespace App\Admin\Infrastructure\Routes;

use App\Shared\Infrastructure\Routing\RouteCollection;

class AdminRoutes extends RouteCollection
{
    public function register(): void
    {
        // Admin middleware
        $this->router->before('GET|POST', '/admin/.*', function() {
            $this->container->get('admin_middleware')->handle();
        });

        // Dashboard
        $this->router->get('/admin/dashboard', function() {
            $this->container->get('admin_controller')->dashboard();
        });

        // Products management
        $this->router->get('/admin/products', function() {
            $this->container->get('admin_controller')->products();
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

        // Orders management
        $this->router->get('/admin/orders', function() {
            $filters = [
                'status' => $_GET['status'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null
            ];
            $this->container->get('admin_controller')->orders($filters);
        });

        $this->router->get('/admin/orders/(\d+)', function($id) {
            $this->container->get('admin_controller')->showOrder($id);
        });

        $this->router->post('/admin/orders/(\d+)/status', function($id) {
            $this->container->get('admin_controller')->updateOrderStatus($id);
        });

        $this->router->get('/admin/orders/(\d+)/fulfillment', function($orderId) {
            $this->container->get('order_fulfillment_controller')->show($orderId);
        });

        $this->router->post('/admin/orders/(\d+)/process', function($orderId) {
            $this->container->get('order_fulfillment_controller')->process($orderId);
        });

        $this->router->post('/admin/orders/(\d+)/ship', function($orderId) {
            $this->container->get('order_fulfillment_controller')->ship($orderId);
        });

        $this->router->post('/admin/orders/(\d+)/complete', function($orderId) {
            $this->container->get('order_fulfillment_controller')->complete($orderId);
        });

        // User Management Routes
        $this->router->get('/admin/users', function() {
            $this->container->get('user_controller')->index();
        });

        $this->router->get('/admin/users/create', function() {
            $this->container->get('user_controller')->create();
        });

        $this->router->post('/admin/users', function() {
            $this->container->get('user_controller')->store();
        });

        $this->router->get('/admin/users/(\d+)/edit', function($id) {
            $this->container->get('user_controller')->edit($id);
        });

        $this->router->post('/admin/users/(\d+)', function($id) {
            $this->container->get('user_controller')->update($id);
        });

        $this->router->post('/admin/users/(\d+)/delete', function($id) {
            $this->container->get('user_controller')->delete($id);
        });

        // Reports management
        $this->router->get('/admin/reports', function() {
            $this->container->get('report_controller')->index();
        });

        $this->router->get('/admin/reports/generate', function() {
            $this->container->get('report_controller')->generate();
        });

        // Inventory management
        $this->router->get('/admin/inventory', function() {
            $this->container->get('inventory_controller')->index();
        });

        $this->router->get('/admin/inventory/products/(\d+)', function($id) {
            $this->container->get('inventory_controller')->show($id);
        });

        $this->router->post('/admin/inventory/products/(\d+)/adjust', function($id) {
            $this->container->get('inventory_controller')->adjust($id);
        });
    }
} 