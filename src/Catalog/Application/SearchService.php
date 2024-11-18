<?php

namespace App\Catalog\Application;

use Illuminate\Database\Capsule\Manager as DB;

class SearchService
{
    public function search(array $params): array
    {
        $query = DB::table('products')
            ->select(
                'products.*',
                'categories.name as category_name'
            )
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('products.is_active', true);

        // Apply search filters
        $query = $this->applyFilters($query, $params);
        
        // Apply sorting
        $query = $this->applySorting($query, $params);

        // Get total count before pagination
        $total = $query->count();

        // Apply pagination
        $page = max(1, $params['page'] ?? 1);
        $perPage = min(50, $params['per_page'] ?? 12);
        $items = $query->skip(($page - 1) * $perPage)
                      ->take($perPage)
                      ->get();

        return [
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage)
            ]
        ];
    }

    private function applyFilters($query, array $params)
    {
        // Search by keyword
        if (!empty($params['q'])) {
            $keyword = '%' . $params['q'] . '%';
            $query->where(function($q) use ($keyword) {
                $q->where('products.name', 'like', $keyword)
                  ->orWhere('products.description', 'like', $keyword)
                  ->orWhere('products.sku', 'like', $keyword);
            });
        }

        // Filter by category
        if (!empty($params['category'])) {
            $query->where('products.category_id', $params['category']);
        }

        // Filter by price range
        if (!empty($params['price_min'])) {
            $query->where('products.price', '>=', $params['price_min']);
        }
        if (!empty($params['price_max'])) {
            $query->where('products.price', '<=', $params['price_max']);
        }

        // Filter by availability
        if (isset($params['in_stock']) && $params['in_stock']) {
            $query->where('products.stock', '>', 0);
        }

        // Filter by featured
        if (isset($params['featured']) && $params['featured']) {
            $query->where('products.is_featured', true);
        }

        return $query;
    }

    private function applySorting($query, array $params): object
    {
        $sortField = $params['sort'] ?? 'created_at';
        $sortOrder = $params['order'] ?? 'desc';

        $allowedFields = [
            'name', 'price', 'created_at', 'stock'
        ];

        if (!in_array($sortField, $allowedFields)) {
            $sortField = 'created_at';
        }

        $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy("products.$sortField", $sortOrder);
    }

    public function getFilterOptions(): array
    {
        return [
            'categories' => $this->getCategories(),
            'price_range' => $this->getPriceRange(),
            'attributes' => $this->getAttributes()
        ];
    }

    private function getCategories(): array
    {
        return DB::table('categories')
            ->select('id', 'name', DB::raw('COUNT(products.id) as product_count'))
            ->leftJoin('products', 'categories.id', '=', 'products.category_id')
            ->groupBy('categories.id', 'categories.name')
            ->having('product_count', '>', 0)
            ->get()
            ->all();
    }

    private function getPriceRange(): array
    {
        $result = DB::table('products')
            ->select(
                DB::raw('MIN(price) as min_price'),
                DB::raw('MAX(price) as max_price')
            )
            ->first();

        return [
            'min' => (float)$result->min_price,
            'max' => (float)$result->max_price
        ];
    }

    private function getAttributes(): array
    {
        return DB::table('product_attributes')
            ->select('name', 'values')
            ->groupBy('name', 'values')
            ->get()
            ->all();
    }
} 