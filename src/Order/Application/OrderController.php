<?php

namespace App\Order\Application;

use App\Order\Domain\Order;
use App\Order\Domain\OrderItem;
use App\Order\Infrastructure\OrderRepository;
use App\Catalog\Infrastructure\ProductRepository;
use Jenssegers\Blade\Blade;
use App\Order\Application\OrderNotificationService;

class OrderController
{
    private OrderRepository $orderRepository;
    private ProductRepository $productRepository;
    private Blade $blade;
    private OrderNotificationService $notificationService;

    public function __construct(
        OrderRepository $orderRepository,
        ProductRepository $productRepository,
        OrderNotificationService $notificationService,
        Blade $blade
    ) {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->notificationService = $notificationService;
        $this->blade = $blade;
    }

    public function create(array $data)
    {
        // Validate stock availability
        $product = $this->productRepository->findById($data['product_id']);
        if (!$product || $product->getStock() < $data['quantity']) {
            throw new \Exception('Product not available in requested quantity');
        }

        $orderItem = new OrderItem(
            0,
            $data['product_id'],
            $data['quantity'],
            $product->getPrice()
        );

        $order = new Order(
            0,
            $data['customer_name'],
            $data['customer_email'],
            $orderItem->getTotal(),
            'pending',
            [$orderItem]
        );

        $orderId = $this->orderRepository->create($order);
        return $this->blade->render('orders.success', ['orderId' => $orderId]);
    }

    public function list()
    {
        $orders = $this->orderRepository->findAll();
        return $this->blade->render('orders.list', ['orders' => $orders]);
    }

    public function show(int $id)
    {
        $order = $this->orderRepository->findById($id);
        return $this->blade->render('orders.show', ['order' => $order]);
    }

    public function processOrder(array $data): int
    {
        $orderId = $this->orderRepository->create($data);
        
        // Send confirmation email
        $this->notificationService->sendOrderConfirmation($orderId);
        
        return $orderId;
    }
} 