<?php

namespace App\Recommendation\Application;

use Illuminate\Database\Capsule\Manager as DB;

class RecommendationService
{
    public function getPersonalizedRecommendations(int $userId, int $limit = 6): array
    {
        // Get user's purchase history
        $purchasedProducts = $this->getUserPurchaseHistory($userId);
        
        // Get user's browsing history
        $viewedProducts = $this->getUserViewHistory($userId);
        
        // Get user's categories of interest
        $categories = $this->getUserCategories($userId);
        
        // Get recommendations based on user behavior
        $recommendations = DB::table('products')
            ->select(
                'products.*',
                'categories.name as category_name',
                DB::raw('(
                    CASE 
                        WHEN category_id IN (' . implode(',', $categories) . ') THEN 3
                        ELSE 0 
                    END +
                    CASE 
                        WHEN price BETWEEN avg_price * 0.8 AND avg_price * 1.2 THEN 2
                        ELSE 0 
                    END +
                    CASE 
                        WHEN rating >= 4 THEN 1
                        ELSE 0 
                    END
                ) as relevance_score')
            )
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereNotIn('products.id', $purchasedProducts)
            ->where('products.is_active', true)
            ->where('products.stock', '>', 0)
            ->orderBy('relevance_score', 'desc')
            ->orderBy('products.rating', 'desc')
            ->limit($limit)
            ->get()
            ->all();

        return $this->enrichRecommendations($recommendations);
    }

    public function getSimilarProducts(int $productId, int $limit = 4): array
    {
        $product = DB::table('products')->find($productId);
        if (!$product) {
            return [];
        }

        return DB::table('products')
            ->select(
                'products.*',
                'categories.name as category_name',
                DB::raw('(
                    CASE 
                        WHEN category_id = ? THEN 3
                        ELSE 0 
                    END +
                    CASE 
                        WHEN price BETWEEN ? * 0.8 AND ? * 1.2 THEN 2
                        ELSE 0 
                    END +
                    CASE 
                        WHEN rating >= 4 THEN 1
                        ELSE 0 
                    END
                ) as similarity_score', [$product->category_id, $product->price, $product->price])
            )
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('products.id', '!=', $productId)
            ->where('products.is_active', true)
            ->where('products.stock', '>', 0)
            ->orderBy('similarity_score', 'desc')
            ->orderBy('products.rating', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getTrendingProducts(int $limit = 8): array
    {
        return DB::table('products')
            ->select(
                'products.*',
                'categories.name as category_name',
                DB::raw('(
                    sales_count * 0.5 + 
                    COALESCE(view_count, 0) * 0.3 + 
                    COALESCE(rating, 0) * 0.2
                ) as trending_score')
            )
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('products.is_active', true)
            ->where('products.stock', '>', 0)
            ->orderBy('trending_score', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    private function getUserPurchaseHistory(int $userId): array
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.user_id', $userId)
            ->pluck('order_items.product_id')
            ->all();
    }

    private function getUserViewHistory(int $userId): array
    {
        return DB::table('product_views')
            ->where('user_id', $userId)
            ->orderBy('viewed_at', 'desc')
            ->limit(20)
            ->pluck('product_id')
            ->all();
    }

    private function getUserCategories(int $userId): array
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.user_id', $userId)
            ->distinct()
            ->pluck('products.category_id')
            ->all();
    }

    private function enrichRecommendations(array $products): array
    {
        foreach ($products as &$product) {
            $product->discount_percent = $this->calculateDiscountPercent($product);
            $product->is_new = $this->isNewProduct($product);
            $product->stock_status = $this->getStockStatus($product);
        }
        return $products;
    }

    private function calculateDiscountPercent($product): ?int
    {
        if ($product->original_price && $product->original_price > $product->price) {
            return round(($product->original_price - $product->price) / $product->original_price * 100);
        }
        return null;
    }

    private function isNewProduct($product): bool
    {
        return strtotime($product->created_at) > strtotime('-30 days');
    }

    private function getStockStatus($product): string
    {
        if ($product->stock > 10) {
            return 'in_stock';
        } elseif ($product->stock > 0) {
            return 'low_stock';
        }
        return 'out_of_stock';
    }
} 