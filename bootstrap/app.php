<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Shared\Infrastructure\DatabaseConnection;
use App\Shared\Infrastructure\Security\Authorization\AuthorizationInterface;
use App\Shared\Infrastructure\Security\Authorization\AuthorizationFactory;
use App\Shared\Infrastructure\Security\Authentication\AuthenticationInterface;

// Load Environment Variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize Session
session_start();

// Initialize Database Connection
DatabaseConnection::initialize();

// Initialize Authorization
$authConfig = [
    'type' => 'hybrid',
    'role_hierarchy' => [
        'admin' => [
            'permissions' => ['*'],
            'inherits' => ['manager']
        ],
        'manager' => [
            'permissions' => [
                'view_products',
                'create_product',
                'update_product',
                'view_orders',
                'update_order'
            ],
            'inherits' => ['user']
        ],
        'user' => [
            'permissions' => [
                'view_products',
                'view_own_orders'
            ]
        ]
    ],
    'default_strategy' => 'any',
    'action_strategies' => [
        'delete_product' => 'all', // Require both RBAC and ABAC to allow
        'update_order' => 'all'
    ]
];

$container->singleton(AuthorizationInterface::class, function() use ($container, $authConfig) {
    return AuthorizationFactory::create(
        $authConfig['type'],
        $container->get(AuthenticationInterface::class),
        $authConfig
    );
}); 