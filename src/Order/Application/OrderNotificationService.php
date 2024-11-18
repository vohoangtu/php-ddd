<?php

namespace App\Order\Application;

use App\Shared\Infrastructure\Email\EmailService;
use App\Order\Infrastructure\OrderRepository;

class OrderNotificationService
{
    private EmailService $emailService;
    private OrderRepository $orderRepository;

    public function __construct(
        EmailService $emailService,
        OrderRepository $orderRepository
    ) {
        $this->emailService = $emailService;
        $this->orderRepository = $orderRepository;
    }

    public function sendOrderConfirmation(int $orderId): bool
    {
        $order = $this->orderRepository->findById($orderId);
        if (!$order) {
            return false;
        }

        $orderUrl = $this->generateOrderUrl($orderId);

        return $this->emailService->send(
            $order['order']->customer_email,
            "Order Confirmation #{$orderId}",
            'order-confirmation',
            [
                'order' => $order['order'],
                'items' => $order['items'],
                'orderUrl' => $orderUrl
            ]
        );
    }

    public function sendOrderStatusUpdate(int $orderId): bool
    {
        $order = $this->orderRepository->findById($orderId);
        if (!$order) {
            return false;
        }

        return $this->emailService->send(
            $order['order']->customer_email,
            "Order Status Update #{$orderId}",
            'order-status-update',
            [
                'order' => $order['order'],
                'items' => $order['items']
            ]
        );
    }

    private function generateOrderUrl(int $orderId): string
    {
        return $_ENV['APP_URL'] . "/order/{$orderId}";
    }
} 