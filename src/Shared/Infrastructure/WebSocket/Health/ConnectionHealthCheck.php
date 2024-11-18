<?php

namespace App\Shared\Infrastructure\WebSocket\Health;

use App\Shared\Infrastructure\WebSocket\Connection;
use App\Shared\Infrastructure\WebSocket\WebSocketServerInterface;

class ConnectionHealthCheck
{
    private WebSocketServerInterface $server;
    private int $heartbeatInterval;
    private int $timeout;

    public function __construct(
        WebSocketServerInterface $server,
        int $heartbeatInterval = 30,
        int $timeout = 120
    ) {
        $this->server = $server;
        $this->heartbeatInterval = $heartbeatInterval;
        $this->timeout = $timeout;
    }

    public function start(): void
    {
        $loop = \React\EventLoop\Loop::get();
        
        $loop->addPeriodicTimer($this->heartbeatInterval, function () {
            $this->checkConnections();
        });
    }

    private function checkConnections(): void
    {
        $now = time();
        
        foreach ($this->server->getConnections() as $connection) {
            $lastPing = $connection->getMetadata('last_ping', 0);
            
            if ($now - $lastPing > $this->timeout) {
                $connection->close();
                continue;
            }

            $this->sendPing($connection);
        }
    }

    private function sendPing(Connection $connection): void
    {
        $connection->send([
            'type' => 'ping',
            'timestamp' => time()
        ]);
    }

    public function handlePong(Connection $connection): void
    {
        $connection->setMetadata('last_ping', time());
    }
} 