<?php

namespace App\Order\Domain;

class OrderItem
{
    private int $id;
    private int $productId;
    private int $quantity;
    private float $price;

    public function __construct(
        int $id,
        int $productId,
        int $quantity,
        float $price
    ) {
        $this->id = $id;
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->price = $price;
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getProductId(): int { return $this->productId; }
    public function getQuantity(): int { return $this->quantity; }
    public function getPrice(): float { return $this->price; }
    public function getTotal(): float { return $this->price * $this->quantity; }
} 