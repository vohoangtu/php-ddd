<?php

namespace App\Cart\Application;

use App\Cart\Domain\CartItem;
use App\Catalog\Infrastructure\ProductRepository;

class CartService
{
    private ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
        
        // Initialize cart session if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    public function addItem(int $productId, int $quantity = 1): void
    {
        $product = $this->productRepository->findById($productId);
        
        if (!$product) {
            throw new \Exception('Product not found');
        }

        if ($product->getStock() < $quantity) {
            throw new \Exception('Insufficient stock');
        }

        $cartItem = new CartItem(
            $product->getId(),
            $product->getName(),
            $product->getPrice(),
            $quantity,
            $product->getImage()
        );

        // Update quantity if product already exists in cart
        if (isset($_SESSION['cart'][$productId])) {
            $existingQuantity = $_SESSION['cart'][$productId]['quantity'];
            $newQuantity = $existingQuantity + $quantity;
            
            if ($product->getStock() < $newQuantity) {
                throw new \Exception('Insufficient stock');
            }
            
            $cartItem->setQuantity($newQuantity);
        }

        $_SESSION['cart'][$productId] = $cartItem->toArray();
    }

    public function updateQuantity(int $productId, int $quantity): void
    {
        if (!isset($_SESSION['cart'][$productId])) {
            throw new \Exception('Product not found in cart');
        }

        $product = $this->productRepository->findById($productId);
        
        if (!$product) {
            throw new \Exception('Product not found');
        }

        if ($product->getStock() < $quantity) {
            throw new \Exception('Insufficient stock');
        }

        if ($quantity <= 0) {
            $this->removeItem($productId);
            return;
        }

        $_SESSION['cart'][$productId]['quantity'] = $quantity;
    }

    public function removeItem(int $productId): void
    {
        unset($_SESSION['cart'][$productId]);
    }

    public function clear(): void
    {
        $_SESSION['cart'] = [];
    }

    public function getItems(): array
    {
        return array_values($_SESSION['cart']);
    }

    public function getCount(): int
    {
        return array_sum(array_column($_SESSION['cart'], 'quantity'));
    }

    public function getSubtotal(): float
    {
        $subtotal = 0;
        foreach ($_SESSION['cart'] as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        return $subtotal;
    }

    public function calculateTax(): float
    {
        return $this->getSubtotal() * 0.1; // 10% tax rate
    }

    public function calculateShipping(): float
    {
        // Basic shipping calculation
        $baseRate = 10.00;
        $itemCount = $this->getCount();
        
        if ($itemCount > 5) {
            return $baseRate + ($itemCount - 5) * 2;
        }
        
        return $baseRate;
    }

    public function getTotal(): float
    {
        return $this->getSubtotal() + $this->calculateTax() + $this->calculateShipping();
    }

    public function validateStock(): array
    {
        $errors = [];
        
        foreach ($_SESSION['cart'] as $productId => $item) {
            $product = $this->productRepository->findById($productId);
            
            if (!$product) {
                $errors[] = "Product ID $productId not found";
                continue;
            }

            if ($product->getStock() < $item['quantity']) {
                $errors[] = "Insufficient stock for product ID $productId";
            }
        }

        return $errors;
    }
} 