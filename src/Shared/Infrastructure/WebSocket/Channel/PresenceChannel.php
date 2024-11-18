<?php

namespace App\Shared\Infrastructure\WebSocket\Channel;

use App\Shared\Infrastructure\WebSocket\Connection;
use App\Shared\Infrastructure\Cache\CacheInterface;

class PresenceChannel
{
    private string $name;
    private CacheInterface $cache;
    private array $members = [];

    public function __construct(string $name, CacheInterface $cache)
    {
        $this->name = $name;
        $this->cache = $cache;
    }

    public function join(Connection $connection, array $userInfo): void
    {
        $userId = $connection->getUserId();
        if (!$userId) {
            return;
        }

        $this->members[$userId] = $userInfo;
        $this->cache->set($this->getCacheKey(), $this->members);

        $this->broadcastPresence('presence.joined', $connection, $userInfo);
    }

    public function leave(Connection $connection): void
    {
        $userId = $connection->getUserId();
        if (!isset($this->members[$userId])) {
            return;
        }

        $userInfo = $this->members[$userId];
        unset($this->members[$userId]);
        $this->cache->set($this->getCacheKey(), $this->members);

        $this->broadcastPresence('presence.left', $connection, $userInfo);
    }

    public function getMembers(): array
    {
        return $this->members;
    }

    private function broadcastPresence(string $event, Connection $connection, array $userInfo): void
    {
        $connection->send([
            'type' => $event,
            'channel' => $this->name,
            'user' => $userInfo
        ]);
    }

    private function getCacheKey(): string
    {
        return "presence_channel:{$this->name}:members";
    }
} 