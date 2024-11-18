<?php

namespace App\Admin\Application;

use App\Catalog\Infrastructure\ProductRepository;
use App\Order\Infrastructure\OrderRepository;
use App\User\Infrastructure\UserRepository;
use Jenssegers\Blade\Blade;

class AdminController
{
    private ProductRepository $productRepository;
    private OrderRepository $orderRepository;
    private UserRepository $userRepository;
    private Blade $blade;

    public function __construct(
        ProductRepository $productRepository,
        OrderRepository $orderRepository,
        UserRepository $userRepository,
        Blade $blade
    ) {
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->userRepository = $userRepository;
        $this->blade = $blade;
    }

    public function dashboard()
    {
        $stats = $this->analyticsService->getDashboardStats();
        
        echo $this->blade->make('admin.dashboard', [
            'stats' => $stats
        ])->render();
    }

    public function products()
    {
        $products = $this->productRepository->findAll();
        echo $this->blade->make('admin.products', [
            'products' => $products
        ])->render();
    }

    public function orders(array $filters = [])
    {
        $orders = $this->orderRepository->findAll($filters);
        $statistics = $this->orderRepository->getOrderStatistics();

        echo $this->blade->make('admin.orders.index', [
            'orders' => $orders,
            'statistics' => $statistics,
            'filters' => $filters
        ])->render();
    }

    public function showOrder(int $id)
    {
        $order = $this->orderRepository->findById($id);
        if (!$order) {
            $_SESSION['error'] = 'Order not found';
            header('Location: /admin/orders');
            exit;
        }

        echo $this->blade->make('admin.orders.show', [
            'order' => $order
        ])->render();
    }

    public function updateOrderStatus(int $id)
    {
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
        if (!$status) {
            $_SESSION['error'] = 'Invalid status';
            header("Location: /admin/orders/$id");
            exit;
        }

        if ($this->orderRepository->updateStatus($id, $status)) {
            $_SESSION['success'] = 'Order status updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update order status';
        }

        header("Location: /admin/orders/$id");
        exit;
    }

    public function createProduct()
    {
        echo $this->blade->make('admin.products.create')->render();
    }

    public function storeProduct()
    {
        $data = [
            'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'description' => filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
            'price' => filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT),
            'stock' => filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT)
        ];

        if (!$data['name'] || !$data['price'] || !$data['stock']) {
            $_SESSION['error'] = 'All fields are required';
            header('Location: /admin/products/create');
            exit;
        }

        $this->productRepository->create($data);
        $_SESSION['success'] = 'Product created successfully';
        header('Location: /admin/products');
        exit;
    }

    public function editProduct(int $id)
    {
        $product = $this->productRepository->findById($id);
        if (!$product) {
            header('Location: /admin/products');
            exit;
        }

        echo $this->blade->make('admin.products.edit', [
            'product' => $product
        ])->render();
    }

    public function updateProduct(int $id)
    {
        $data = [
            'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'description' => filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
            'price' => filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT),
            'stock' => filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT)
        ];

        if (!$data['name'] || !$data['price'] || !$data['stock']) {
            $_SESSION['error'] = 'All fields are required';
            header("Location: /admin/products/$id/edit");
            exit;
        }

        $this->productRepository->update($id, $data);
        $_SESSION['success'] = 'Product updated successfully';
        header('Location: /admin/products');
        exit;
    }
} 