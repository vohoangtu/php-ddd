<?php

namespace App\Api\Controllers;

use App\Shared\Infrastructure\Api\ApiResponse;
use App\Catalog\Infrastructure\ProductRepository;

class ProductApiController
{
    private ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function index(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        $category = $_GET['category'] ?? null;
        
        $products = $this->productRepository->findAll([
            'page' => $page,
            'limit' => $limit,
            'category' => $category
        ]);

        ApiResponse::success([
            'products' => $products,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $this->productRepository->count()
            ]
        ]);
    }

    public function show(int $id): void
    {
        $product = $this->productRepository->findById($id);
        
        if (!$product) {
            ApiResponse::error('Product not found', 404);
        }

        ApiResponse::success(['product' => $product]);
    }
} 