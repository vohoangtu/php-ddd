<?php

namespace App\Catalog\Domain;

class Product
{
    private int $id;
    private string $name;
    private string $description;
    private float $price;
    private int $stock;

    public function __construct(
        int $id,
        string $name,
        string $description,
        float $price,
        int $stock
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->stock = $stock;
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getPrice(): float { return $this->price; }
    public function getStock(): int { return $this->stock; }
} 