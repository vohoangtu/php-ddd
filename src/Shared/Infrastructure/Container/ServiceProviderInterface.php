<?php
namespace App\Shared\Infrastructure\Container;

interface ServiceProviderInterface
{
    public function register(Container $container): void;
    public function provides(): array;
} 