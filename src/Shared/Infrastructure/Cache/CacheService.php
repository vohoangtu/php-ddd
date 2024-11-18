<?php
namespace App\Shared\Infrastructure\Cache;

use Redis;
use App\Shared\Infrastructure\Error\ErrorHandler;

class CacheService
{
    private Redis $redis;
    private ErrorHandler $errorHandler;
    private array $defaultTtl = [
        'product' => 3600,        // 1 hour
        'category' => 7200,       // 2 hours
        'user' => 1800,          // 30 minutes
        'settings' => 86400,      // 24 hours
        'menu' => 3600,          // 1 hour
        'search' => 900,         // 15 minutes
        'recommendations' => 1800 // 30 minutes
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

    public function get(string $key, string $type = null)
    {
        try {
            $value = $this->redis->get($this->formatKey($key, $type));
            
            if ($value === false) {
                return null;
            }

            return $this->unserialize($value);
        } catch (\Exception $e) {
            $this->errorHandler->logError('Cache get error', [
                'error' => $e->getMessage(),
                'key' => $key,
                'type' => $type
            ]);
            return null;
        }
    }

    public function set(string $key, $value, string $type = null, ?int $ttl = null): bool
    {
        try {
            $ttl = $ttl ?? ($type ? $this->defaultTtl[$type] ?? 3600 : 3600);
            
            return $this->redis->setex(
                $this->formatKey($key, $type),
                $ttl,
                $this->serialize($value)
            );
        } catch (\Exception $e) {
            $this->errorHandler->logError('Cache set error', [
                'error' => $e->getMessage(),
                'key' => $key,
                'type' => $type
            ]);
            return false;
        }
    }

    public function delete(string $key, string $type = null): bool
    {
        try {
            return $this->redis->del($this->formatKey($key, $type)) > 0;
        } catch (\Exception $e) {
            $this->errorHandler->logError('Cache delete error', [
                'error' => $e->getMessage(),
                'key' => $key,
                'type' => $type
            ]);
            return false;
        }
    }

    public function clear(string $pattern): void
    {
        try {
            $keys = $this->redis->keys($pattern);
            if (!empty($keys)) {
                $this->redis->del($keys);
            }
        } catch (\Exception $e) {
            $this->errorHandler->logError('Cache clear error', [
                'error' => $e->getMessage(),
                'pattern' => $pattern
            ]);
        }
    }

    public function remember(string $key, callable $callback, string $type = null, ?int $ttl = null)
    {
        $value = $this->get($key, $type);
        
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $type, $ttl);
        
        return $value;
    }

    public function tags(array $tags): TaggedCache
    {
        return new TaggedCache($this, $tags);
    }

    public function increment(string $key, int $value = 1, string $type = null): int
    {
        try {
            return $this->redis->incrBy($this->formatKey($key, $type), $value);
        } catch (\Exception $e) {
            $this->errorHandler->logError('Cache increment error', [
                'error' => $e->getMessage(),
                'key' => $key,
                'type' => $type
            ]);
            return 0;
        }
    }

    public function decrement(string $key, int $value = 1, string $type = null): int
    {
        try {
            return $this->redis->decrBy($this->formatKey($key, $type), $value);
        } catch (\Exception $e) {
            $this->errorHandler->logError('Cache decrement error', [
                'error' => $e->getMessage(),
                'key' => $key,
                'type' => $type
            ]);
            return 0;
        }
    }

    private function formatKey(string $key, ?string $type): string
    {
        return $type ? "cache:{$type}:{$key}" : "cache:{$key}";
    }

    private function serialize($value): string
    {
        return serialize($value);
    }

    private function unserialize(string $value)
    {
        return unserialize($value);
    }
}