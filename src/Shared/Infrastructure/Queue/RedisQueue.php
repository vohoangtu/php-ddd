<?php

namespace App\Shared\Infrastructure\Queue;

use Predis\Client;
use App\Shared\Infrastructure\Serializer\SerializerInterface;

class RedisQueue implements QueueInterface
{
    private Client $redis;
    private SerializerInterface $serializer;
    private string $prefix;

    public function __construct(
        Client $redis, 
        SerializerInterface $serializer,
        string $prefix = 'queue:'
    ) {
        $this->redis = $redis;
        $this->serializer = $serializer;
        $this->prefix = $prefix;
    }

    public function push(string $queue, JobInterface $job): void
    {
        $payload = $this->createPayload($job);
        $this->redis->rpush($this->getQueue($queue), [$payload]);
    }

    public function later(string $queue, JobInterface $job, \DateTimeInterface $delay): void
    {
        $payload = $this->createPayload($job);
        $this->redis->zadd(
            $this->getQueue($queue . ':delayed'),
            [$payload => $delay->getTimestamp()]
        );
    }

    public function pop(string $queue): ?JobInterface
    {
        // Move delayed jobs if needed
        $this->migrateDelayedJobs($queue);

        $payload = $this->redis->lpop($this->getQueue($queue));
        if (!$payload) {
            return null;
        }

        return $this->serializer->deserialize($payload, JobInterface::class);
    }

    private function migrateDelayedJobs(string $queue): void
    {
        $now = time();
        $delayedQueue = $this->getQueue($queue . ':delayed');

        // Get jobs that should be processed now
        $jobs = $this->redis->zrangebyscore(
            $delayedQueue,
            '-inf',
            $now
        );

        if (empty($jobs)) {
            return;
        }

        // Move jobs to the main queue
        $this->redis->rpush($this->getQueue($queue), $jobs);
        
        // Remove from delayed queue
        $this->redis->zremrangebyscore($delayedQueue, '-inf', $now);
    }

    private function createPayload(JobInterface $job): string
    {
        return $this->serializer->serialize($job);
    }

    private function getQueue(string $queue): string
    {
        return $this->prefix . $queue;
    }
} 