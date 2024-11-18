<?php

namespace App\Shared\Domain\Event;

abstract class AbstractEvent implements EventInterface
{
    protected array $payload;
    protected int $timestamp;

    public function __construct(array $payload = [])
    {
        $this->payload = $payload;
        $this->timestamp = time();
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getName(): string
    {
        return static::class;
    }
} 