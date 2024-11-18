<?php

namespace App\Shared\Infrastructure\Container\Provider;

use App\Shared\Infrastructure\Container\{Container, ServiceProviderInterface};
use App\Shared\Infrastructure\Error\ErrorHandler;
use App\Shared\Infrastructure\Cache\CacheService;
use App\Shared\Infrastructure\RateLimiter\RateLimiter;

class CoreServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        // Error Handler
        $container->singleton('error_handler', function () {
            return new ErrorHandler();
        });

        // Cache Service
        $container->singleton('cache', function ($container) {
            return new CacheService(
                $container->get('error_handler')
            );
        });

        // Rate Limiter
        $container->singleton('rate_limiter', function ($container) {
            return new RateLimiter(
                $container->get('error_handler')
            );
        });
    }

    public function provides(): array
    {
        return [
            'error_handler',
            'cache',
            'rate_limiter'
        ];
    }
} 