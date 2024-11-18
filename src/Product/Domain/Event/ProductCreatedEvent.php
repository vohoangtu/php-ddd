<?php

namespace App\Product\Domain\Event;

use App\Shared\Domain\Event\AbstractEvent;

class ProductCreatedEvent extends AbstractEvent
{
    public function getName(): string
    {
        return 'product.created';
    }
} 