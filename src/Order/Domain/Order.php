<?php
namespace App\Order\Domain;

class Order
{
    private int $id;
    private string $customerName;
    private string $customerEmail;
    private float $totalAmount;
    private string $status;
    private array $items;

    public function __construct(
        int $id,
        string $customerName,
        string $customerEmail,
        float $totalAmount,
        string $status,
        array $items = []
    ) {
        $this->id = $id;
        $this->customerName = $customerName;
        $this->customerEmail = $customerEmail;
        $this->totalAmount = $totalAmount;
        $this->status = $status;
        $this->items = $items;
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getCustomerName(): string { return $this->customerName; }
    public function getCustomerEmail(): string { return $this->customerEmail; }
    public function getTotalAmount(): float { return $this->totalAmount; }
    public function getStatus(): string { return $this->status; }
    public function getItems(): array { return $this->items; }
} 