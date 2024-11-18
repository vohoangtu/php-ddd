<?php
namespace App\Shared\Infrastructure\Queue;

interface QueueInterface
{
    public function push(string $queue, JobInterface $job): void;
    public function later(string $queue, JobInterface $job, \DateTimeInterface $delay): void;
    public function pop(string $queue): ?JobInterface;
    public function delete(string $queue, string $jobId): void;
    public function release(string $queue, JobInterface $job, int $delay = 0): void;
} 