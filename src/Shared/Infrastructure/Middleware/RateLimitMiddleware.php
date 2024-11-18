<?php

namespace App\Shared\Infrastructure\Middleware;

use App\Shared\Infrastructure\RateLimiter\RateLimiter;

class RateLimitMiddleware
{
    private RateLimiter $rateLimiter;

    public function __construct(RateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    public function handle(string $type): bool
    {
        $key = $this->getClientIdentifier();
        
        if (!$this->rateLimiter->attempt($key, $type)) {
            $this->sendRateLimitResponse($key, $type);
            return false;
        }

        $this->addRateLimitHeaders($key, $type);
        return true;
    }

    private function getClientIdentifier(): string
    {
        if (isset($_SESSION['user_id'])) {
            return 'user:' . $_SESSION['user_id'];
        }

        return 'ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    private function sendRateLimitResponse(string $key, string $type): void
    {
        header('HTTP/1.1 429 Too Many Requests');
        header('Content-Type: application/json');
        header('Retry-After: ' . $this->rateLimiter->getResetTime($key, $type));
        
        echo json_encode([
            'error' => 'Too many requests',
            'message' => 'Please try again later',
            'retry_after' => $this->rateLimiter->getResetTime($key, $type)
        ]);
        exit;
    }

    private function addRateLimitHeaders(string $key, string $type): void
    {
        header('X-RateLimit-Remaining: ' . $this->rateLimiter->getRemainingAttempts($key, $type));
        header('X-RateLimit-Reset: ' . $this->rateLimiter->getResetTime($key, $type));
    }
} 