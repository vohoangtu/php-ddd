<?php

namespace App\Shared\Infrastructure\Security\RateLimit;

use Predis\Client;

class RedisRateLimiter implements RateLimiterInterface
{
    private Client $redis;
    private string $prefix;

    public function __construct(Client $redis, string $prefix = 'ratelimit:')
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
    }

    public function attempt(string $key, int $maxAttempts, int $decayMinutes): bool
    {
        $key = $this->prefix . $key;

        $this->redis->multi();
        $this->redis->incr($key);
        $this->redis->expire($key, $decayMinutes * 60);
        $attempts = $this->redis->exec()[0];

        if ($attempts > $maxAttempts) {
            return false;
        }

        return true;
    }

    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        $key = $this->prefix . $key;
        return (int) $this->redis->get($key) >= $maxAttempts;
    }

    public function resetAttempts(string $key): void
    {
        $this->redis->del([$this->prefix . $key]);
    }

    public function availableIn(string $key): int
    {
        $key = $this->prefix . $key;
        return max(0, $this->redis->ttl($key));
    }
} 