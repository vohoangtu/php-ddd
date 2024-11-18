<?php

namespace App\Product\Domain\Notification;

use App\Shared\Domain\Notification\NotificationInterface;
use App\Product\Domain\Entity\Product;
use App\User\Domain\Entity\User;

class ProductCreatedNotification implements NotificationInterface
{
    private Product $product;
    private User $user;

    public function __construct(Product $product, User $user)
    {
        $this->product = $product;
        $this->user = $user;
    }

    public function getType(): string
    {
        return 'email';
    }

    public function getData(): array
    {
        return [
            'subject' => 'New Product Created',
            'product_name' => $this->product->getName(),
            'product_price' => $this->product->getPrice(),
            'created_by' => $this->user->getName()
        ];
    }

    public function getRecipient(): string
    {
        return $this->user->getEmail();
    }

    public function getTemplate(): string
    {
        return 'emails.product-created';
    }
} 