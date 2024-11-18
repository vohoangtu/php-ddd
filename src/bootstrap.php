<?php
use App\Shared\Infrastructure\Container\Container;
use App\Shared\Infrastructure\Container\Provider\{
    CoreServiceProvider,
    WebServiceProvider,
    NotificationServiceProvider
};

$container = new Container();

// Register Service Providers
$container->register(new CoreServiceProvider());
$container->register(new WebServiceProvider());
$container->register(new NotificationServiceProvider());

// Add more providers as needed...

return $container; 