<?php

namespace App\Inventory\Application;

use Illuminate\Database\Capsule\Manager as DB;
use App\Inventory\Domain\StockMovement;
use App\Inventory\Domain\Exception\InsufficientStockException;

class InventoryService
{
    public function adjustStock(int $productId, int $quantity, string $reason, ?string $reference = null): void
    {
        DB::beginTransaction();
        
        try {
            // Get current stock
            $product = DB::table('products')
                ->where('id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                throw new \Exception('Product not found');
            }

            // Calculate new stock level
            $newStock = $product->stock + $quantity;
            
            // Prevent negative stock unless allowed by configuration
            if ($newStock < 0 && !config('inventory.allow_negative_stock')) {
                throw new InsufficientStockException(
                    "Insufficient stock for product {$product->name}"
                );
            }

            // Update product stock
            DB::table('products')
                ->where('id', $productId)
                ->update([
                    'stock' => $newStock,
                    'updated_at' => now()
                ]);

            // Record stock movement
            $this->recordStockMovement(
                $productId,
                $quantity,
                $reason,
                $reference
            );

            // Check low stock threshold
            $this->checkLowStockAlert($productId, $newStock, $product->low_stock_threshold);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function checkLowStockAlert(int $productId, int $currentStock, int $threshold): void
    {
        if ($currentStock <= $threshold) {
            // Record alert
            DB::table('inventory_alerts')->insert([
                'product_id' => $productId,
                'type' => 'low_stock',
                'message' => "Product stock is low ({$currentStock} items remaining)",
                'created_at' => now()
            ]);

            // Notify administrators (implement notification logic)
        }
    }

    public function recordStockMovement(
        int $productId,
        int $quantity,
        string $reason,
        ?string $reference
    ): void {
        DB::table('stock_movements')->insert([
            'product_id' => $productId,
            'quantity' => $quantity,
            'reason' => $reason,
            'reference' => $reference,
            'created_at' => now()
        ]);
    }

    public function getStockMovements(int $productId): array
    {
        return DB::table('stock_movements')
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function getLowStockProducts(int $limit = 10): array
    {
        return DB::table('products')
            ->select('products.*', 'categories.name as category_name')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereRaw('stock <= low_stock_threshold')
            ->orderBy('stock', 'asc')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getProductInventoryDetails(int $productId): array
    {
        $product = DB::table('products')
            ->select('products.*', 'categories.name as category_name')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('products.id', $productId)
            ->first();

        if (!$product) {
            throw new \Exception('Product not found');
        }

        return [
            'product' => $product,
            'movements' => $this->getStockMovements($productId),
            'alerts' => $this->getProductAlerts($productId)
        ];
    }

    private function getProductAlerts(int $productId): array
    {
        return DB::table('inventory_alerts')
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }
} 