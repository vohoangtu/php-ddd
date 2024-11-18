<?php
namespace App\Shared\Infrastructure\Security\SSO\Server\Token;

use Firebase\JWT\JWT;

class TokenManager implements TokenManagerInterface
{
    private string $secretKey;
    private array $config;
    private CacheInterface $cache;

    public function __construct(
        string $secretKey,
        CacheInterface $cache,
        array $config = []
    ) {
        $this->secretKey = $secretKey;
        $this->cache = $cache;
        $this->config = array_merge([
            'access_token_lifetime' => 3600,
            'refresh_token_lifetime' => 86400,
            'token_algorithm' => 'HS256',
        ], $config);
    }

    public function generateTokens(string $sessionId, array $scopes): TokenPair
    {
        $accessToken = $this->createAccessToken($sessionId, $scopes);
        $refreshToken = $this->createRefreshToken($sessionId);

        return new TokenPair($accessToken, $refreshToken);
    }

    public function validateToken(string $token): ?TokenData
    {
        try {
            if ($this->isTokenBlacklisted($token)) {
                return null;
            }

            $payload = JWT::decode(
                $token,
                $this->secretKey,
                [$this->config['token_algorithm']]
            );

            return new TokenData(
                $payload->sub,
                $payload->scopes ?? [],
                $payload->exp
            );

        } catch (\Exception $e) {
            return null;
        }
    }

    public function revokeToken(string $token): void
    {
        $this->blacklistToken($token);
    }

    private function createAccessToken(string $sessionId, array $scopes): string
    {
        $now = time();
        
        $payload = [
            'iat' => $now,
            'exp' => $now + $this->config['access_token_lifetime'],
            'sub' => $sessionId,
            'scopes' => $scopes,
            'type' => 'access'
        ];

        return JWT::encode($payload, $this->secretKey, $this->config['token_algorithm']);
    }

    private function createRefreshToken(string $sessionId): string
    {
        $now = time();
        
        $payload = [
            'iat' => $now,
            'exp' => $now + $this->config['refresh_token_lifetime'],
            'sub' => $sessionId,
            'type' => 'refresh'
        ];

        return JWT::encode($payload, $this->secretKey, $this->config['token_algorithm']);
    }

    private function blacklistToken(string $token): void
    {
        $key = "blacklisted_token:{$token}";
        $this->cache->set($key, true, $this->config['access_token_lifetime']);
    }

    private function isTokenBlacklisted(string $token): bool
    {
        return $this->cache->has("blacklisted_token:{$token}");
    }
} 