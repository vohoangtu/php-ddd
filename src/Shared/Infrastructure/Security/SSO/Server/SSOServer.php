<?php

namespace App\Shared\Infrastructure\Security\SSO\Server;

use App\Shared\Infrastructure\Security\Encryption\EncryptionInterface;
use App\Shared\Infrastructure\Cache\CacheInterface;
use App\Shared\Infrastructure\Database\DatabaseInterface;

class SSOServer
{
    private DatabaseInterface $database;
    private CacheInterface $cache;
    private EncryptionInterface $encryption;
    private array $config;
    private TokenManagerInterface $tokenManager;
    private SessionManagerInterface $sessionManager;

    public function __construct(
        DatabaseInterface $database,
        CacheInterface $cache,
        EncryptionInterface $encryption,
        TokenManagerInterface $tokenManager,
        SessionManagerInterface $sessionManager,
        array $config = []
    ) {
        $this->database = $database;
        $this->cache = $cache;
        $this->encryption = $encryption;
        $this->tokenManager = $tokenManager;
        $this->sessionManager = $sessionManager;
        $this->config = array_merge([
            'session_lifetime' => 3600,
            'token_lifetime' => 300,
            'allowed_domains' => [],
            'require_2fa' => false,
            'protocols' => ['saml', 'oidc', 'oauth2'],
        ], $config);
    }

    public function handleAuthRequest(array $params): AuthResponse
    {
        $protocol = $this->detectProtocol($params);
        $handler = $this->getProtocolHandler($protocol);

        try {
            // Validate request
            $this->validateRequest($params, $protocol);

            // Handle authentication
            $authResult = $handler->authenticate($params);

            // Create SSO session
            $session = $this->sessionManager->createSession(
                $authResult->getUserId(),
                $authResult->getAttributes(),
                $protocol
            );

            // Generate tokens
            $tokens = $this->tokenManager->generateTokens(
                $session->getId(),
                $authResult->getScopes()
            );

            return new AuthResponse(
                $session,
                $tokens,
                $authResult->getRedirectUrl()
            );

        } catch (SSOException $e) {
            $this->logError($e);
            throw $e;
        }
    }

    public function validateToken(string $token): ?SessionData
    {
        try {
            $tokenData = $this->tokenManager->validateToken($token);
            if (!$tokenData) {
                return null;
            }

            return $this->sessionManager->getSession($tokenData->getSessionId());

        } catch (SSOException $e) {
            $this->logError($e);
            return null;
        }
    }

    private function validateRequest(array $params, string $protocol): void
    {
        // Validate client
        if (!$this->isValidClient($params['client_id'])) {
            throw new SSOException('Invalid client');
        }

        // Validate redirect URI
        if (!$this->isValidRedirectUri($params['redirect_uri'])) {
            throw new SSOException('Invalid redirect URI');
        }

        // Protocol-specific validation
        $handler = $this->getProtocolHandler($protocol);
        $handler->validateRequest($params);
    }

    private function isValidClient(string $clientId): bool
    {
        return $this->database->table('sso_clients')
            ->where('client_id', $clientId)
            ->where('active', true)
            ->exists();
    }

    private function isValidRedirectUri(string $uri): bool
    {
        $domain = parse_url($uri, PHP_URL_HOST);
        return in_array($domain, $this->config['allowed_domains']);
    }
} 