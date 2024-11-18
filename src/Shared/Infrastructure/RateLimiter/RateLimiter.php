<?php

namespace App\Shared\Infrastructure\RateLimiter;

use Redis;
use App\Shared\Infrastructure\Error\ErrorHandler;

class RateLimiter
{
    private Redis $redis;
    private ErrorHandler $errorHandler;
    private array $limits = [
        'api' => [
            'requests' => 60,
            'period' => 60 // seconds
        ],
        'login' => [
            'requests' => 5,
            'period' => 300 // 5 minutes
        ],
        'register' => [
            'requests' => 3,
            'period' => 3600 // 1 hour
        ],
        'password_reset' => [
            'requests' => 3,
            'period' => 3600
        ],
        'email' => [
            'requests' => 20,
            'period' => 3600
        ]
    ];

    public function __construct(ErrorHandler $errorHandler)
    {
        $this->errorHandler = $errorHandler;
        $this->initializeRedis();
    }

    private function initializeRedis(): void
    {
        try {
            $this->redis = new Redis();
            $this->redis->connect(
                $_ENV['REDIS_HOST'],
                $_ENV['REDIS_PORT']
            );
            
            if ($_ENV['REDIS_PASSWORD']) {
                $this->redis->auth($_ENV['REDIS_PASSWORD']);
            }
        } catch (\Exception $e) {
            $this->errorHandler->logError('Redis connection failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function attempt(string $key, string $type): bool
    {
        if (!isset($this->limits[$type])) {
            throw new \InvalidArgumentException("Unknown rate limit type: {$type}");
        }

        $limit = $this->limits[$type];
        $redisKey = "ratelimit:{$type}:{$key}";

        try {
            $current = $this->redis->get($redisKey);
            
            if ($current === false) {
                // First attempt
                $this->redis->setex($redisKey, $limit['period'], 1);
                return true;
            }

            if ($current >= $limit['requests']) {
                $ttl = $this->redis->ttl($redisKey);
                $this->logRateLimitExceeded($key, $type, $ttl);
                return false;
            }

            $this->redis->incr($redisKey);
            return true;
        } catch (\Exception $e) {
            $this->errorHandler->logError('Rate limiter error', [
                'error' => $e->getMessage(),
                'key' => $key,
                'type' => $type
            ]);
            return true; // Fail open if Redis is down
        }
    }

    public function getRemainingAttempts(string $key, string $type): int
    {
        if (!isset($this->limits[$type])) {
            throw new \InvalidArgumentException("Unknown rate limit type: {$type}");
        }

        try {
            $redisKey = "ratelimit:{$type}:{$key}";
            $current = $this->redis->get($redisKey);
            
            if ($current === false) {
                return $this->limits[$type]['requests'];
            }

            return max(0, $this->limits[$type]['requests'] - $current);
        } catch (\Exception $e) {
            $this->errorHandler->logError('Rate limiter error', [
                'error' => $e->getMessage(),
                'key' => $key,
                'type' => $type
            ]);
            return 0;
        }
    }

    public function getResetTime(string $key, string $type): ?int
    {
        try {
            $redisKey = "ratelimit:{$type}:{$key}";
            return $this->redis->ttl($redisKey);
        } catch (\Exception $e) {
            $this->errorHandler->logError('Rate limiter error', [
                'error' => $e->getMessage(),
                'key' => $key,
                'type' => $type
            ]);
            return null;
        }
    }

    private function logRateLimitExceeded(string $key, string $type, int $ttl): void
    {
        $this->errorHandler->logWarning('Rate limit exceeded', [
            'key' => $key,
            'type' => $type,
            'reset_in' => $ttl,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
} 