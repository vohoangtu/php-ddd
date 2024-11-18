<?php
namespace App\Product\Domain\Entity;

use App\Shared\Domain\Entity\AbstractEntity;

class Product extends AbstractEntity
{
    protected string $name;
    protected string $description;
    protected float $price;
    protected int $stock;
    protected bool $isActive;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    // ... other getters and setters
} 