<?php
namespace App\Shared\Infrastructure\Security\RateLimit;

interface RateLimiterInterface
{
    public function attempt(string $key, int $maxAttempts, int $decayMinutes): bool;
    public function tooManyAttempts(string $key, int $maxAttempts): bool;
    public function resetAttempts(string $key): void;
    public function availableIn(string $key): int;
} 