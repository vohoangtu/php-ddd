<?php

namespace App\Order\Infrastructure;

use Illuminate\Database\Capsule\Manager as DB;

class OrderRepository
{
    public function create(array $orderData): int
    {
        DB::beginTransaction();
        
        try {
            // Create order
            $orderId = DB::table('orders')->insertGetId([
                'customer_name' => $orderData['customer_name'],
                'customer_email' => $orderData['customer_email'],
                'total_amount' => $orderData['total_amount'],
                'status' => $orderData['status'],
                'created_at' => now()
            ]);

            // Create order items
            foreach ($orderData['items'] as $item) {
                DB::table('order_items')->insert([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'created_at' => now()
                ]);

                // Update product stock
                DB::table('products')
                    ->where('id', $item['product_id'])
                    ->decrement('stock', $item['quantity']);
            }

            DB::commit();
            return $orderId;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function findById(int $id): ?array
    {
        $order = DB::table('orders')->find($id);
        if (!$order) return null;

        $items = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_id', $id)
            ->select('order_items.*', 'products.name as product_name')
            ->get();

        return [
            'order' => $order,
            'items' => $items
        ];
    }

    public function findAll(array $filters = []): array
    {
        $query = DB::table('orders')
            ->orderBy('created_at', 'desc');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->get()->all();
    }

    public function updateStatus(int $orderId, string $status): bool
    {
        return DB::table('orders')
            ->where('id', $orderId)
            ->update([
                'status' => $status,
                'updated_at' => now()
            ]) > 0;
    }

    public function getOrderStatistics(): array
    {
        return [
            'total' => DB::table('orders')->count(),
            'pending' => DB::table('orders')->where('status', 'pending')->count(),
            'processing' => DB::table('orders')->where('status', 'processing')->count(),
            'completed' => DB::table('orders')->where('status', 'completed')->count(),
            'cancelled' => DB::table('orders')->where('status', 'cancelled')->count(),
            'total_revenue' => DB::table('orders')
                ->where('status', 'completed')
                ->sum('total_amount')
        ];
    }
} 