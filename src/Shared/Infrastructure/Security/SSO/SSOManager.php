<?php

namespace App\Shared\Infrastructure\Security\SSO;

class SSOManager
{
    private array $providers;
    private AdvancedSessionManager $sessionManager;
    private SessionActivityMonitor $activityMonitor;

    public function __construct(
        array $providers,
        AdvancedSessionManager $sessionManager,
        SessionActivityMonitor $activityMonitor
    ) {
        $this->providers = $providers;
        $this->sessionManager = $sessionManager;
        $this->activityMonitor = $activityMonitor;
    }

    public function authenticate(
        string $provider,
        array $params,
        array $deviceInfo
    ): array {
        if (!isset($this->providers[$provider])) {
            throw new SSOException("Unknown provider: {$provider}");
        }

        $ssoProvider = $this->providers[$provider];
        $userData = $ssoProvider->handleCallback($params);

        $sessionId = $this->sessionManager->createSession(
            $userData,
            $provider,
            $deviceInfo
        );

        $this->activityMonitor->recordActivity($sessionId, $userData->getId(), [
            'type' => 'authentication',
            'provider' => $provider,
            'device_info' => $deviceInfo
        ]);

        return [
            'session_id' => $sessionId,
            'token' => $this->sessionManager->getCurrentToken($sessionId),
            'user' => $userData,
            'expires_in' => $this->sessionManager->getSessionTTL($sessionId)
        ];
    }

    public function validateSession(string $sessionId, string $token): bool
    {
        return $this->sessionManager->validateSession($sessionId, $token);
    }

    public function refreshSession(string $sessionId): array
    {
        return $this->sessionManager->refreshSession($sessionId);
    }

    public function logout(string $sessionId): void
    {
        $this->sessionManager->invalidateSession($sessionId);
    }
} 