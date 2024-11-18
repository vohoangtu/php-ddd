<?php

namespace App\Shared\Infrastructure\Security\SSO\Session;

use App\Shared\Infrastructure\Cache\CacheInterface;
use App\Shared\Infrastructure\Queue\QueueInterface;

class SessionActivityMonitor
{
    private CacheInterface $cache;
    private QueueInterface $queue;

    public function __construct(CacheInterface $cache, QueueInterface $queue)
    {
        $this->cache = $cache;
        $this->queue = $queue;
    }

    public function recordActivity(
        string $sessionId,
        string $userId,
        array $activity
    ): void {
        $activityData = array_merge($activity, [
            'timestamp' => microtime(true),
            'session_id' => $sessionId,
            'user_id' => $userId
        ]);

        // Store recent activity in cache
        $key = "session_activity:{$sessionId}";
        $activities = $this->cache->get($key, []);
        array_unshift($activities, $activityData);
        $activities = array_slice($activities, 0, 10); // Keep last 10 activities
        $this->cache->set($key, $activities);

        // Queue for analytics processing
        $this->queue->push('session.activity', [
            'type' => 'activity_recorded',
            'data' => $activityData
        ]);
    }

    public function detectSuspiciousActivity(array $activity): bool
    {
        // Implement suspicious activity detection logic
        // For example: rapid location changes, unusual patterns, etc.
        return false;
    }

    public function getSessionActivity(string $sessionId): array
    {
        return $this->cache->get("session_activity:{$sessionId}", []);
    }
} 