<?php

namespace App\Shared\Infrastructure\WebSocket;

use App\Shared\Domain\Event\EventInterface;

class WebSocketEvent implements EventInterface
{
    private string $name;
    private array $payload;
    private int $timestamp;

    public function __construct(string $name, array $payload = [])
    {
        $this->name = $name;
        $this->payload = $payload;
        $this->timestamp = time();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }
} 