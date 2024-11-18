<?php

namespace App\Shared\Infrastructure\Container\Provider;

use App\Shared\Infrastructure\Container\{Container, ServiceProviderInterface};
use App\Notification\Application\EmailNotificationService;
use App\Notification\Infrastructure\Queue\EmailQueue;

class NotificationServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        // Email Service
        $container->singleton('email_service', function () {
            return new EmailNotificationService();
        });

        // Email Queue
        $container->singleton('email_queue', function ($container) {
            return new EmailQueue(
                $container->get('error_handler')
            );
        });
    }

    public function provides(): array
    {
        return [
            'email_service',
            'email_queue'
        ];
    }
} 