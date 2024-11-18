<?php

namespace App\Shared\Infrastructure\Security\SSO;

use App\Shared\Infrastructure\Cache\CacheInterface;
use App\Shared\Infrastructure\Security\Session\SessionInterface;

class SSOSessionManager
{
    private CacheInterface $cache;
    private SessionInterface $session;
    private string $sessionPrefix;

    public function __construct(
        CacheInterface $cache,
        SessionInterface $session,
        string $sessionPrefix = 'sso_session:'
    ) {
        $this->cache = $cache;
        $this->session = $session;
        $this->sessionPrefix = $sessionPrefix;
    }

    public function createSession(SSOUserData $userData, string $provider): void
    {
        $sessionId = $this->generateSessionId();
        
        $sessionData = [
            'user' => $userData,
            'provider' => $provider,
            'created_at' => time(),
            'last_activity' => time()
        ];

        $this->cache->set(
            $this->getSessionKey($sessionId),
            $sessionData,
            3600 // 1 hour
        );

        $this->session->set('sso_session_id', $sessionId);
    }

    public function validateSession(): bool
    {
        $sessionId = $this->session->get('sso_session_id');
        if (!$sessionId) {
            return false;
        }

        $sessionData = $this->cache->get($this->getSessionKey($sessionId));
        if (!$sessionData) {
            return false;
        }

        // Update last activity
        $sessionData['last_activity'] = time();
        $this->cache->set(
            $this->getSessionKey($sessionId),
            $sessionData,
            3600
        );

        return true;
    }

    public function getCurrentUser(): ?SSOUserData
    {
        $sessionId = $this->session->get('sso_session_id');
        if (!$sessionId) {
            return null;
        }

        $sessionData = $this->cache->get($this->getSessionKey($sessionId));
        return $sessionData ? $sessionData['user'] : null;
    }

    private function generateSessionId(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function getSessionKey(string $sessionId): string
    {
        return $this->sessionPrefix . $sessionId;
    }
} 