<?php

namespace App\Shared\Infrastructure\Security\SSO\Server\Session;

use App\Shared\Infrastructure\Cache\CacheInterface;
use App\Shared\Infrastructure\Database\DatabaseInterface;

class SessionManager implements SessionManagerInterface
{
    private CacheInterface $cache;
    private DatabaseInterface $database;
    private array $config;

    public function __construct(
        CacheInterface $cache,
        DatabaseInterface $database,
        array $config = []
    ) {
        $this->cache = $cache;
        $this->database = $database;
        $this->config = array_merge([
            'session_lifetime' => 3600,
            'session_refresh_threshold' => 300,
            'max_sessions_per_user' => 5,
        ], $config);
    }

    public function createSession(
        string $userId,
        array $attributes,
        string $protocol
    ): SessionData {
        // Clean old sessions if needed
        $this->cleanOldSessions($userId);

        $sessionId = $this->generateSessionId();
        $session = new SessionData(
            $sessionId,
            $userId,
            $attributes,
            $protocol,
            time() + $this->config['session_lifetime']
        );

        $this->saveSession($session);
        $this->trackUserSession($userId, $sessionId);

        return $session;
    }

    public function getSession(string $sessionId): ?SessionData
    {
        $data = $this->cache->get("session:{$sessionId}");
        if (!$data) {
            return null;
        }

        return SessionData::fromArray($data);
    }

    public function refreshSession(string $sessionId): ?SessionData
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            return null;
        }

        $session->setExpiresAt(time() + $this->config['session_lifetime']);
        $this->saveSession($session);

        return $session;
    }

    private function saveSession(SessionData $session): void
    {
        $this->cache->set(
            "session:{$session->getId()}",
            $session->toArray(),
            $this->config['session_lifetime']
        );

        $this->database->table('sso_sessions')->insert([
            'session_id' => $session->getId(),
            'user_id' => $session->getUserId(),
            'protocol' => $session->getProtocol(),
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', $session->getExpiresAt())
        ]);
    }

    private function cleanOldSessions(string $userId): void
    {
        $sessions = $this->database->table('sso_sessions')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        if (count($sessions) >= $this->config['max_sessions_per_user']) {
            foreach (array_slice($sessions, $this->config['max_sessions_per_user'] - 1) as $session) {
                $this->invalidateSession($session->session_id);
            }
        }
    }

    private function invalidateSession(string $sessionId): void
    {
        $this->cache->delete("session:{$sessionId}");
        $this->database->table('sso_sessions')
            ->where('session_id', $sessionId)
            ->delete();
    }

    private function generateSessionId(): string
    {
        return bin2hex(random_bytes(32));
    }
} 