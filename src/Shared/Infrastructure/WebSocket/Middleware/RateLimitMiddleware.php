<?php

namespace App\Shared\Infrastructure\WebSocket\Middleware;

use App\Shared\Infrastructure\WebSocket\WebSocketMiddlewareInterface;
use App\Shared\Infrastructure\WebSocket\Connection;
use App\Shared\Infrastructure\Cache\CacheInterface;

class RateLimitMiddleware implements WebSocketMiddlewareInterface
{
    private CacheInterface $cache;
    private int $maxRequests;
    private int $timeWindow;

    public function __construct(
        CacheInterface $cache,
        int $maxRequests = 60,
        int $timeWindow = 60
    ) {
        $this->cache = $cache;
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
    }

    public function handle(Connection $connection, array $data): bool
    {
        $key = sprintf('ws:ratelimit:%d', $connection->getResourceId());
        $requests = (int) $this->cache->get($key, 0);

        if ($requests >= $this->maxRequests) {
            $connection->send([
                'type' => 'error',
                'message' => 'Rate limit exceeded'
            ]);
            return false;
        }

        $this->cache->set($key, $requests + 1, $this->timeWindow);
        return true;
    }
} 