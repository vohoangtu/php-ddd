<?php

namespace App\Api\Controllers;

use App\Shared\Infrastructure\Api\ApiResponse;
use App\Order\Infrastructure\OrderRepository;

class OrderApiController
{
    private OrderRepository $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function index(): void
    {
        $user = $_REQUEST['auth_user'];
        $orders = $this->orderRepository->findByUser($user->getId());
        
        ApiResponse::success(['orders' => $orders]);
    }

    public function store(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateOrderData($data)) {
            ApiResponse::error('Invalid order data', 422);
        }

        try {
            $orderId = $this->orderRepository->create($data);
            $order = $this->orderRepository->findById($orderId);
            
            ApiResponse::success(
                ['order' => $order],
                'Order created successfully',
                201
            );
        } catch (\Exception $e) {
            ApiResponse::error('Failed to create order: ' . $e->getMessage(), 500);
        }
    }

    private function validateOrderData(array $data): bool
    {
        return isset($data['items']) && 
               is_array($data['items']) && 
               !empty($data['items']);
    }
} 