<?php
namespace App\Shared\Infrastructure\Security\SSO\Session;

use App\Shared\Infrastructure\Cache\CacheInterface;
use App\Shared\Infrastructure\Security\Encryption\EncryptionInterface;
use App\Shared\Infrastructure\Security\SSO\SSOUserData;

class AdvancedSessionManager
{
    private CacheInterface $cache;
    private EncryptionInterface $encryption;
    private array $config;

    public function __construct(
        CacheInterface $cache,
        EncryptionInterface $encryption,
        array $config = []
    ) {
        $this->cache = $cache;
        $this->encryption = $encryption;
        $this->config = array_merge([
            'session_lifetime' => 3600,
            'refresh_threshold' => 300,
            'max_sessions' => 5,
            'single_session' => false,
            'track_devices' => true,
            'rotation_interval' => 300,
        ], $config);
    }

    public function createSession(
        SSOUserData $userData, 
        string $provider,
        array $deviceInfo
    ): string {
        $sessionId = $this->generateSessionId();
        $userId = $userData->getId();

        // Enforce single session if configured
        if ($this->config['single_session']) {
            $this->invalidateUserSessions($userId);
        }

        // Manage maximum sessions per user
        $this->enforceMaxSessions($userId);

        $sessionData = [
            'id' => $sessionId,
            'user' => $userData,
            'provider' => $provider,
            'device_info' => $deviceInfo,
            'created_at' => time(),
            'last_activity' => time(),
            'last_rotated' => time(),
            'token_chain' => [$this->generateToken()],
        ];

        $this->saveSession($sessionId, $sessionData);
        $this->trackUserSession($userId, $sessionId);

        return $sessionId;
    }

    public function validateSession(string $sessionId, string $token): bool
    {
        $sessionData = $this->getSession($sessionId);
        if (!$sessionData) {
            return false;
        }

        // Validate token chain
        if (!in_array($token, $sessionData['token_chain'])) {
            return false;
        }

        // Check session lifetime
        if (time() - $sessionData['created_at'] > $this->config['session_lifetime']) {
            $this->invalidateSession($sessionId);
            return false;
        }

        // Check if token rotation is needed
        if ($this->needsRotation($sessionData)) {
            $sessionData = $this->rotateToken($sessionData);
        }

        // Update last activity
        $sessionData['last_activity'] = time();
        $this->saveSession($sessionId, $sessionData);

        return true;
    }

    public function refreshSession(string $sessionId): array
    {
        $sessionData = $this->getSession($sessionId);
        if (!$sessionData) {
            throw new SessionException('Invalid session');
        }

        $sessionData = $this->rotateToken($sessionData);
        $this->saveSession($sessionId, $sessionData);

        return [
            'token' => end($sessionData['token_chain']),
            'expires_in' => $this->config['session_lifetime']
        ];
    }

    private function rotateToken(array $sessionData): array
    {
        $newToken = $this->generateToken();
        $sessionData['token_chain'][] = $newToken;
        
        // Keep only the last few tokens in the chain
        if (count($sessionData['token_chain']) > 3) {
            array_shift($sessionData['token_chain']);
        }

        $sessionData['last_rotated'] = time();
        return $sessionData;
    }

    private function needsRotation(array $sessionData): bool
    {
        return (time() - $sessionData['last_rotated']) > $this->config['rotation_interval'];
    }

    private function trackUserSession(string $userId, string $sessionId): void
    {
        $key = "user_sessions:{$userId}";
        $sessions = $this->cache->get($key, []);
        $sessions[$sessionId] = time();
        $this->cache->set($key, $sessions);
    }

    private function enforceMaxSessions(string $userId): void
    {
        $key = "user_sessions:{$userId}";
        $sessions = $this->cache->get($key, []);

        if (count($sessions) >= $this->config['max_sessions']) {
            // Remove oldest session
            asort($sessions);
            $oldestSessionId = array_key_first($sessions);
            $this->invalidateSession($oldestSessionId);
            unset($sessions[$oldestSessionId]);
            $this->cache->set($key, $sessions);
        }
    }

    private function saveSession(string $sessionId, array $data): void
    {
        $encrypted = $this->encryption->encrypt(json_encode($data));
        $this->cache->set(
            "session:{$sessionId}",
            $encrypted,
            $this->config['session_lifetime']
        );
    }

    private function getSession(string $sessionId): ?array
    {
        $encrypted = $this->cache->get("session:{$sessionId}");
        if (!$encrypted) {
            return null;
        }

        return json_decode(
            $this->encryption->decrypt($encrypted),
            true
        );
    }

    private function generateSessionId(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
} 