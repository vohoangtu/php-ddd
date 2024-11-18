<?php
namespace App\Shared\Infrastructure\WebSocket;

interface WebSocketMiddlewareInterface
{
    public function handle(Connection $connection, array $data): bool;
} 