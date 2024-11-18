<?php

namespace App\Admin\Application;

use App\Order\Application\OrderFulfillmentService;
use Jenssegers\Blade\Blade;

class OrderFulfillmentController
{
    private OrderFulfillmentService $fulfillmentService;
    private Blade $blade;

    public function __construct(
        OrderFulfillmentService $fulfillmentService,
        Blade $blade
    ) {
        $this->fulfillmentService = $fulfillmentService;
        $this->blade = $blade;
    }

    public function show(int $orderId): void
    {
        try {
            $data = $this->fulfillmentService->getOrderFulfillmentDetails($orderId);
            
            echo $this->blade->make('admin.orders.fulfillment', [
                'order' => $data['order'],
                'items' => $data['items'],
                'fulfillment' => $data['fulfillment'],
                'shipment' => $data['shipment'],
                'timeline' => $data['timeline']
            ])->render();
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /admin/orders');
            exit;
        }
    }

    public function process(int $orderId): void
    {
        try {
            $this->fulfillmentService->processOrder($orderId);
            
            $_SESSION['success'] = "Order #$orderId has been processed successfully";
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                echo json_encode(['success' => true]);
                return;
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                http_response_code(400);
                echo json_encode(['error' => $e->getMessage()]);
                return;
            }
        }
        
        header("Location: /admin/orders/$orderId/fulfillment");
        exit;
    }

    public function ship(int $orderId): void
    {
        try {
            $shipmentData = [
                'carrier' => filter_input(INPUT_POST, 'carrier', FILTER_SANITIZE_STRING),
                'tracking_number' => filter_input(INPUT_POST, 'tracking_number', FILTER_SANITIZE_STRING)
            ];

            if (empty($shipmentData['carrier']) || empty($shipmentData['tracking_number'])) {
                throw new \Exception('Carrier and tracking number are required');
            }

            $this->fulfillmentService->shipOrder($orderId, $shipmentData);
            
            $_SESSION['success'] = "Order #$orderId has been marked as shipped";
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                echo json_encode(['success' => true]);
                return;
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                http_response_code(400);
                echo json_encode(['error' => $e->getMessage()]);
                return;
            }
        }
        
        header("Location: /admin/orders/$orderId/fulfillment");
        exit;
    }

    public function complete(int $orderId): void
    {
        try {
            $this->fulfillmentService->completeOrder($orderId);
            
            $_SESSION['success'] = "Order #$orderId has been completed";
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                echo json_encode(['success' => true]);
                return;
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                http_response_code(400);
                echo json_encode(['error' => $e->getMessage()]);
                return;
            }
        }
        
        header("Location: /admin/orders/$orderId/fulfillment");
        exit;
    }
} 