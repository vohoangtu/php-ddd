<?php

namespace App\Catalog\Application;

class CartService
{
    public function __construct()
    {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    public function addItem(int $productId, int $quantity = 1): void
    {
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
    }

    public function removeItem(int $productId): void
    {
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
        }
    }

    public function updateQuantity(int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($productId);
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
    }

    public function getItems(): array
    {
        return $_SESSION['cart'];
    }

    public function clear(): void
    {
        $_SESSION['cart'] = [];
    }
} 