<?php

namespace App\Shared\Infrastructure\Http\Middleware;

use App\Shared\Infrastructure\Security\Authorization\AuthorizationInterface;

class AuthorizationMiddleware
{
    private AuthorizationInterface $authorization;
    private array $config;

    public function __construct(
        AuthorizationInterface $authorization,
        array $config = []
    ) {
        $this->authorization = $authorization;
        $this->config = array_merge([
            'unauthorized_redirect' => '/login',
            'exclude_paths' => []
        ], $config);
    }

    public function handle(callable $next): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Skip excluded paths
        if (in_array($path, $this->config['exclude_paths'])) {
            $next();
            return;
        }

        // Map path to required permission
        $permission = $this->mapPathToPermission($path);
        if (!$permission) {
            $next();
            return;
        }

        // Check authorization
        if (!$this->authorization->can($permission, $this->getContext())) {
            $_SESSION['error'] = 'Unauthorized access';
            header('Location: ' . $this->config['unauthorized_redirect']);
            exit;
        }

        $next();
    }

    private function mapPathToPermission(string $path): ?string
    {
        // Example mapping - customize based on your needs
        return match (true) {
            str_starts_with($path, '/admin/products') => 'manage_products',
            str_starts_with($path, '/admin/orders') => 'manage_orders',
            str_starts_with($path, '/admin/users') => 'manage_users',
            default => null
        };
    }

    private function getContext(): array
    {
        return [
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'request_time' => $_SERVER['REQUEST_TIME'],
            // Add any other relevant context
        ];
    }
} 