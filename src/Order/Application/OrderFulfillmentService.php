<?php
namespace App\Order\Application;

use Illuminate\Database\Capsule\Manager as DB;
use App\Order\Domain\OrderStatus;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Notification\Application\NotificationService;

class OrderFulfillmentService
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function processOrder(int $orderId): void
    {
        DB::beginTransaction();
        
        try {
            $order = DB::table('orders')
                ->where('id', $orderId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new OrderNotFoundException("Order #$orderId not found");
            }

            // Validate order status
            if ($order->status !== OrderStatus::PAID) {
                throw new \Exception('Order must be paid before processing');
            }

            // Update order status
            DB::table('orders')
                ->where('id', $orderId)
                ->update([
                    'status' => OrderStatus::PROCESSING,
                    'processed_at' => now(),
                    'updated_at' => now()
                ]);

            // Create fulfillment record
            $fulfillmentId = DB::table('order_fulfillments')->insertGetId([
                'order_id' => $orderId,
                'status' => 'pending',
                'created_at' => now()
            ]);

            // Process order items
            $this->processOrderItems($orderId, $fulfillmentId);

            // Notify customer
            $this->notificationService->sendOrderProcessingNotification($order);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function shipOrder(int $orderId, array $shipmentData): void
    {
        DB::beginTransaction();
        
        try {
            $order = DB::table('orders')
                ->where('id', $orderId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new OrderNotFoundException("Order #$orderId not found");
            }

            // Validate order status
            if ($order->status !== OrderStatus::PROCESSING) {
                throw new \Exception('Order must be processing before shipping');
            }

            // Create shipment record
            $shipmentId = DB::table('shipments')->insertGetId([
                'order_id' => $orderId,
                'carrier' => $shipmentData['carrier'],
                'tracking_number' => $shipmentData['tracking_number'],
                'shipping_date' => now(),
                'created_at' => now()
            ]);

            // Update order status
            DB::table('orders')
                ->where('id', $orderId)
                ->update([
                    'status' => OrderStatus::SHIPPED,
                    'shipped_at' => now(),
                    'updated_at' => now()
                ]);

            // Update fulfillment status
            DB::table('order_fulfillments')
                ->where('order_id', $orderId)
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'updated_at' => now()
                ]);

            // Notify customer
            $this->notificationService->sendShipmentNotification($order, $shipmentData);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function completeOrder(int $orderId): void
    {
        DB::beginTransaction();
        
        try {
            $order = DB::table('orders')
                ->where('id', $orderId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new OrderNotFoundException("Order #$orderId not found");
            }

            // Validate order status
            if ($order->status !== OrderStatus::SHIPPED) {
                throw new \Exception('Order must be shipped before completing');
            }

            // Update order status
            DB::table('orders')
                ->where('id', $orderId)
                ->update([
                    'status' => OrderStatus::COMPLETED,
                    'completed_at' => now(),
                    'updated_at' => now()
                ]);

            // Send thank you email
            $this->notificationService->sendOrderCompletedNotification($order);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function processOrderItems(int $orderId, int $fulfillmentId): void
    {
        $items = DB::table('order_items')
            ->where('order_id', $orderId)
            ->get();

        foreach ($items as $item) {
            // Add item to fulfillment
            DB::table('fulfillment_items')->insert([
                'fulfillment_id' => $fulfillmentId,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'created_at' => now()
            ]);

            // Update product stock
            DB::table('products')
                ->where('id', $item->product_id)
                ->decrement('stock', $item->quantity);
        }
    }

    public function getOrderFulfillmentDetails(int $orderId): array
    {
        $order = DB::table('orders')
            ->select('orders.*', 'users.name as customer_name')
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->where('orders.id', $orderId)
            ->first();

        if (!$order) {
            throw new OrderNotFoundException("Order #$orderId not found");
        }

        return [
            'order' => $order,
            'items' => $this->getOrderItems($orderId),
            'fulfillment' => $this->getFulfillmentDetails($orderId),
            'shipment' => $this->getShipmentDetails($orderId),
            'timeline' => $this->getOrderTimeline($orderId)
        ];
    }

    private function getOrderItems(int $orderId): array
    {
        return DB::table('order_items')
            ->select(
                'order_items.*',
                'products.name as product_name',
                'products.sku'
            )
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->where('order_id', $orderId)
            ->get()
            ->all();
    }

    private function getFulfillmentDetails(int $orderId): ?object
    {
        return DB::table('order_fulfillments')
            ->where('order_id', $orderId)
            ->first();
    }

    private function getShipmentDetails(int $orderId): ?object
    {
        return DB::table('shipments')
            ->where('order_id', $orderId)
            ->first();
    }

    private function getOrderTimeline(int $orderId): array
    {
        return DB::table('order_status_history')
            ->where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }
} 