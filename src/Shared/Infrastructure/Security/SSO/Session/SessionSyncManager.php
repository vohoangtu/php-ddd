<?php
namespace App\Shared\Infrastructure\Security\SSO\Session;

use App\Shared\Infrastructure\Cache\CacheInterface;
use App\Shared\Infrastructure\Queue\QueueInterface;
use App\Shared\Infrastructure\WebSocket\WebSocketServerInterface;
use App\Shared\Infrastructure\Security\SSO\Session\Conflict\ConflictResolver;

class SessionSyncManager
{
    private CacheInterface $cache;
    private QueueInterface $queue;
    private WebSocketServerInterface $wsServer;
    private array $config;
    private ConflictResolver $conflictResolver;

    public function __construct(
        CacheInterface $cache,
        QueueInterface $queue,
        WebSocketServerInterface $wsServer,
        array $config = []
    ) {
        $this->cache = $cache;
        $this->queue = $queue;
        $this->wsServer = $wsServer;
        $this->config = array_merge([
            'sync_interval' => 30,
            'sync_channel' => 'session_sync',
            'broadcast_changes' => true,
        ], $config);
        $this->conflictResolver = new ConflictResolver($config);
    }

    public function syncSession(string $userId, string $sessionId, array $changes): void
    {
        $syncKey = "session_sync:{$userId}";
        $deviceKey = "session_device:{$sessionId}";

        // Record changes with timestamp
        $changeData = [
            'timestamp' => microtime(true),
            'session_id' => $sessionId,
            'changes' => $changes,
            'device_info' => $this->cache->get($deviceKey)
        ];

        // Store in sync history
        $syncHistory = $this->cache->get($syncKey, []);
        array_unshift($syncHistory, $changeData);
        $syncHistory = array_slice($syncHistory, 0, 50); // Keep last 50 changes
        
        $this->cache->set($syncKey, $syncHistory);

        if ($this->config['broadcast_changes']) {
            $this->broadcastChanges($userId, $changeData);
        }

        // Queue sync event for processing
        $this->queue->push('session.sync', [
            'user_id' => $userId,
            'data' => $changeData
        ]);
    }

    public function getDeviceSessions(string $userId): array
    {
        $sessions = [];
        $devicePattern = "session_device:{$userId}:*";
        
        foreach ($this->cache->keys($devicePattern) as $key) {
            $deviceInfo = $this->cache->get($key);
            if ($deviceInfo) {
                $sessions[] = [
                    'session_id' => str_replace("session_device:{$userId}:", '', $key),
                    'device_info' => $deviceInfo,
                    'last_sync' => $deviceInfo['last_sync'] ?? null
                ];
            }
        }

        return $sessions;
    }

    private function broadcastChanges(string $userId, array $changeData): void
    {
        $this->wsServer->broadcastToUser($userId, [
            'type' => 'session_sync',
            'data' => $changeData
        ]);
    }

    public function handleConflicts(array $changes): array
    {
        $resolvedChanges = [];
        $conflicts = [];

        foreach ($changes as $change) {
            $key = $change['key'];
            if (isset($resolvedChanges[$key])) {
                if ($this->isConflicting($resolvedChanges[$key], $change)) {
                    $conflicts[] = [
                        'key' => $key,
                        'changes' => [$resolvedChanges[$key], $change]
                    ];
                }
            } else {
                $resolvedChanges[$key] = $change;
            }
        }

        return [
            'resolved' => $resolvedChanges,
            'conflicts' => $conflicts
        ];
    }

    private function isConflicting(array $existing, array $new): bool
    {
        return $existing['timestamp'] > $new['timestamp'] - $this->config['sync_interval'];
    }

    public function handleSync(array $changes, string $sessionId): array
    {
        $conflictGroups = $this->groupConflictingChanges($changes);
        $resolved = [];

        foreach ($conflictGroups as $group) {
            if (count($group) === 1) {
                $resolved[] = $group[0];
                continue;
            }

            $resolution = $this->conflictResolver->resolveConflict(
                $this->determineResolutionType($group),
                $group,
                [
                    'session_id' => $sessionId,
                    'device_info' => $this->getDeviceInfo($sessionId)
                ]
            );

            if ($resolution->hasConflicts()) {
                $this->handleUnresolvedConflicts($resolution);
            }

            $resolved[] = $resolution->getData();
        }

        return $resolved;
    }

    private function groupConflictingChanges(array $changes): array
    {
        $groups = [];
        
        foreach ($changes as $change) {
            $key = $change['key'];
            $groups[$key][] = $change;
        }

        return array_values($groups);
    }

    private function determineResolutionType(array $changes): string
    {
        // Analyze changes to determine best resolution strategy
        $types = array_column($changes, 'type');
        
        if (in_array('array', $types)) {
            return 'merge';
        } elseif (count(array_unique($types)) === 1) {
            return 'version_vector';
        }

        return 'custom';
    }
}