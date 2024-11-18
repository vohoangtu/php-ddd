<?php

namespace App\Shared\Infrastructure\WebSocket\StateSync;

use App\Shared\Infrastructure\WebSocket\Connection;
use App\Shared\Infrastructure\Cache\CacheInterface;

class StateSyncManager
{
    private CacheInterface $cache;
    private array $states = [];

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function setState(string $channel, string $key, mixed $value): void
    {
        $state = $this->getChannelState($channel);
        $state[$key] = [
            'value' => $value,
            'timestamp' => microtime(true)
        ];

        $this->states[$channel] = $state;
        $this->cache->set($this->getStateCacheKey($channel), $state);
    }

    public function syncState(Connection $connection, string $channel): void
    {
        $state = $this->getChannelState($channel);
        
        $connection->send([
            'type' => 'state.sync',
            'channel' => $channel,
            'state' => $state
        ]);
    }

    public function getDiff(string $channel, float $since): array
    {
        $state = $this->getChannelState($channel);
        $diff = [];

        foreach ($state as $key => $data) {
            if ($data['timestamp'] > $since) {
                $diff[$key] = $data['value'];
            }
        }

        return $diff;
    }

    private function getChannelState(string $channel): array
    {
        if (!isset($this->states[$channel])) {
            $this->states[$channel] = $this->cache->get(
                $this->getStateCacheKey($channel),
                []
            );
        }

        return $this->states[$channel];
    }

    private function getStateCacheKey(string $channel): string
    {
        return "channel_state:{$channel}";
    }
} 