<?php

namespace App\Shared\Infrastructure\Cache;

class TaggedCache
{
    private CacheService $cache;
    private array $tags;

    public function __construct(CacheService $cache, array $tags)
    {
        $this->cache = $cache;
        $this->tags = $tags;
    }

    public function get(string $key)
    {
        return $this->cache->get($this->taggedKey($key));
    }

    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $success = $this->cache->set($this->taggedKey($key), $value, null, $ttl);
        
        if ($success) {
            foreach ($this->tags as $tag) {
                $this->cache->increment("tag:{$tag}");
            }
        }
        
        return $success;
    }

    public function delete(string $key): bool
    {
        return $this->cache->delete($this->taggedKey($key));
    }

    public function flush(): void
    {
        foreach ($this->tags as $tag) {
            $this->cache->increment("tag:{$tag}");
        }
    }

    private function taggedKey(string $key): string
    {
        $namespace = implode(':', array_map(function ($tag) {
            return $tag . ':' . $this->cache->get("tag:{$tag}", 0);
        }, $this->tags));

        return "{$namespace}:{$key}";
    }
} 