<?php
namespace App\Shared\Infrastructure\Container\Provider;

use App\Shared\Infrastructure\Container\{Container, ServiceProviderInterface};
use App\Shared\Infrastructure\Routing\Router;
use App\Shared\Infrastructure\Middleware\RateLimitMiddleware;
use Jenssegers\Blade\Blade;

class WebServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        // Router
        $container->singleton('router', function () {
            return new Router();
        });

        // Blade Template Engine
        $container->singleton('blade', function () {
            return new Blade(
                __DIR__ . '/../../../../views',
                __DIR__ . '/../../../../cache/views'
            );
        });

        // Rate Limit Middleware
        $container->singleton('rate_limit_middleware', function ($container) {
            return new RateLimitMiddleware(
                $container->get('rate_limiter')
            );
        });
    }

    public function provides(): array
    {
        return [
            'router',
            'blade',
            'rate_limit_middleware'
        ];
    }
} 