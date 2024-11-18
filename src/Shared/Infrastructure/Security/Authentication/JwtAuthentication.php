<?php

namespace App\Shared\Infrastructure\Security\Authentication;

use Firebase\JWT\JWT;
use App\Shared\Infrastructure\Security\User\UserRepositoryInterface;
use App\Shared\Infrastructure\Cache\CacheInterface;

class JwtAuthentication implements AuthenticationInterface
{
    private UserRepositoryInterface $userRepository;
    private CacheInterface $cache;
    private array $config;

    public function __construct(
        UserRepositoryInterface $userRepository,
        CacheInterface $cache,
        array $config
    ) {
        $this->userRepository = $userRepository;
        $this->cache = $cache;
        $this->config = $config;
    }

    public function authenticate(string $username, string $password): ?AuthTokenInterface
    {
        $user = $this->userRepository->findByUsername($username);
        
        if (!$user || !$this->verifyPassword($password, $user->getPassword())) {
            return null;
        }

        return $this->createToken($user);
    }

    public function validateToken(string $token): bool
    {
        try {
            if ($this->isTokenBlacklisted($token)) {
                return false;
            }

            $payload = JWT::decode(
                $token, 
                $this->config['secret_key'],
                ['HS256']
            );

            return $payload->exp > time();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function createToken(UserInterface $user): AuthTokenInterface
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->config['token_ttl'];

        $payload = [
            'iss' => $this->config['issuer'],
            'aud' => $this->config['audience'],
            'iat' => $issuedAt,
            'exp' => $expire,
            'uid' => $user->getId(),
            'roles' => $user->getRoles()
        ];

        $token = JWT::encode($payload, $this->config['secret_key']);
        $refreshToken = $this->generateRefreshToken($user);

        return new AuthToken($token, $refreshToken, $expire);
    }

    private function isTokenBlacklisted(string $token): bool
    {
        return $this->cache->has("blacklisted_token:{$token}");
    }
} 