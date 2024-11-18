<?php

namespace App\Shared\Application\Service;

use App\Shared\Infrastructure\Database\DatabaseInterface;
use App\Shared\Infrastructure\Cache\CacheService;
use App\Shared\Infrastructure\Error\ErrorHandler;

abstract class AbstractService implements ServiceInterface
{
    protected DatabaseInterface $db;
    protected CacheService $cache;
    protected ErrorHandler $errorHandler;

    public function __construct(
        DatabaseInterface $db,
        CacheService $cache,
        ErrorHandler $errorHandler
    ) {
        $this->db = $db;
        $this->cache = $cache;
        $this->errorHandler = $errorHandler;
    }

    protected function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    protected function commit(): void
    {
        $this->db->commit();
    }

    protected function rollback(): void
    {
        $this->db->rollback();
    }

    protected function withTransaction(callable $callback)
    {
        try {
            $this->beginTransaction();
            $result = $callback();
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    protected function cache(string $key, callable $callback, int $ttl = 3600)
    {
        return $this->cache->remember($key, $callback, null, $ttl);
    }

    protected function clearCache(string $pattern): void
    {
        $this->cache->clear($pattern);
    }

    protected function logError(string $message, array $context = []): void
    {
        $this->errorHandler->logError($message, $context);
    }
} 